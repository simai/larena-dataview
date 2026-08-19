<?php

declare(strict_types=1);

namespace Larena\Dataview\Persistence;

use InvalidArgumentException;
use JsonException;
use Larena\Dataview\Contracts\DataviewDescriptorStore;
use Larena\Dataview\Runtime\DataviewDescriptorNormalizer;
use Larena\Dataview\ValueObjects\DataviewDescriptorRevision;
use PDO;
use Throwable;

final readonly class PdoDataviewDescriptorStore implements DataviewDescriptorStore
{
    public function __construct(private PDO $pdo, private DataviewDescriptorNormalizer $normalizer = new DataviewDescriptorNormalizer())
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function install(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS larena_dataview_descriptors (scope_ref VARCHAR(128) NOT NULL, view_id VARCHAR(120) NOT NULL, current_revision INTEGER NOT NULL, current_json TEXT NOT NULL, semantic_hash CHAR(64) NOT NULL, updated_by VARCHAR(160) NOT NULL, PRIMARY KEY (scope_ref, view_id))');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS larena_dataview_descriptor_versions (scope_ref VARCHAR(128) NOT NULL, view_id VARCHAR(120) NOT NULL, revision INTEGER NOT NULL, document_json TEXT NOT NULL, semantic_hash CHAR(64) NOT NULL, changed_by VARCHAR(160) NOT NULL, PRIMARY KEY (scope_ref, view_id, revision))');
    }

    public function create(array $descriptor, string $actor): DataviewDescriptorRevision
    {
        return $this->persist($descriptor, null, $actor);
    }

    public function update(array $descriptor, int $expectedRevision, string $actor): DataviewDescriptorRevision
    {
        if ($expectedRevision < 1) {
            throw new InvalidArgumentException('dataview_descriptor_revision_invalid');
        }
        return $this->persist($descriptor, $expectedRevision, $actor);
    }

    public function read(string $scopeRef, string $viewId): ?DataviewDescriptorRevision
    {
        try {
            $statement = $this->pdo->prepare('SELECT current_revision, current_json, semantic_hash FROM larena_dataview_descriptors WHERE scope_ref = :scope AND view_id = :view');
            $statement->execute(['scope' => $scopeRef, 'view' => $viewId]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $this->hydrate($scopeRef, $viewId, (int) $row['current_revision'], (string) $row['current_json'], (string) $row['semantic_hash']) : null;
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new InvalidArgumentException('dataview_descriptor_persistence_failed');
        }
    }

    public function history(string $scopeRef, string $viewId): array
    {
        try {
            $statement = $this->pdo->prepare('SELECT revision, document_json, semantic_hash FROM larena_dataview_descriptor_versions WHERE scope_ref = :scope AND view_id = :view ORDER BY revision DESC');
            $statement->execute(['scope' => $scopeRef, 'view' => $viewId]);
            $history = [];
            while (is_array($row = $statement->fetch(PDO::FETCH_ASSOC))) {
                $history[] = $this->hydrate($scopeRef, $viewId, (int) $row['revision'], (string) $row['document_json'], (string) $row['semantic_hash']);
            }
            return $history;
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new InvalidArgumentException('dataview_descriptor_persistence_failed');
        }
    }

    public function rollback(string $scopeRef, string $viewId, int $targetRevision, int $expectedRevision, string $actor): DataviewDescriptorRevision
    {
        if ($targetRevision < 1 || $expectedRevision < 1) {
            throw new InvalidArgumentException('dataview_descriptor_revision_invalid');
        }
        try {
            $statement = $this->pdo->prepare('SELECT document_json, semantic_hash FROM larena_dataview_descriptor_versions WHERE scope_ref = :scope AND view_id = :view AND revision = :revision');
            $statement->execute(['scope' => $scopeRef, 'view' => $viewId, 'revision' => $targetRevision]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                throw new InvalidArgumentException('dataview_descriptor_revision_unknown');
            }
            $target = $this->hydrate($scopeRef, $viewId, $targetRevision, (string) $row['document_json'], (string) $row['semantic_hash']);
            return $this->persist($target->descriptor, $expectedRevision, $actor);
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new InvalidArgumentException('dataview_descriptor_persistence_failed');
        }
    }

    /** @param array<string,mixed> $descriptor */
    private function persist(array $descriptor, ?int $expectedRevision, string $actor): DataviewDescriptorRevision
    {
        $descriptor = $this->normalizer->normalize($descriptor);
        $scope = $descriptor['scope_ref'];
        $view = $descriptor['view_id'];
        $json = $this->normalizer->encode($descriptor);
        $hash = $this->normalizer->hash($descriptor);
        $nested = $this->pdo->inTransaction();
        $nested ? $this->pdo->exec('SAVEPOINT larena_dataview_descriptor_write') : $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare('SELECT current_revision FROM larena_dataview_descriptors WHERE scope_ref = :scope AND view_id = :view');
            $query->execute(['scope' => $scope, 'view' => $view]);
            $current = $query->fetchColumn();
            if ($expectedRevision === null && $current !== false) {
                throw new InvalidArgumentException('dataview_descriptor_already_exists');
            }
            if ($expectedRevision !== null && ($current === false || (int) $current !== $expectedRevision)) {
                throw new InvalidArgumentException('dataview_descriptor_revision_conflict');
            }
            $revision = $expectedRevision === null ? 1 : $expectedRevision + 1;
            $head = $expectedRevision === null
                ? $this->pdo->prepare('INSERT INTO larena_dataview_descriptors (scope_ref, view_id, current_revision, current_json, semantic_hash, updated_by) VALUES (:scope, :view, :revision, :json, :hash, :actor)')
                : $this->pdo->prepare('UPDATE larena_dataview_descriptors SET current_revision = :revision, current_json = :json, semantic_hash = :hash, updated_by = :actor WHERE scope_ref = :scope AND view_id = :view AND current_revision = :expected');
            $values = ['scope' => $scope, 'view' => $view, 'revision' => $revision, 'json' => $json, 'hash' => $hash, 'actor' => $actor];
            if ($expectedRevision !== null) {
                $values['expected'] = $expectedRevision;
            }
            $head->execute($values);
            if ($head->rowCount() !== 1) {
                throw new InvalidArgumentException('dataview_descriptor_revision_conflict');
            }
            $version = $this->pdo->prepare('INSERT INTO larena_dataview_descriptor_versions (scope_ref, view_id, revision, document_json, semantic_hash, changed_by) VALUES (:scope, :view, :revision, :json, :hash, :actor)');
            $version->execute(['scope' => $scope, 'view' => $view, 'revision' => $revision, 'json' => $json, 'hash' => $hash, 'actor' => $actor]);
            $nested ? $this->pdo->exec('RELEASE SAVEPOINT larena_dataview_descriptor_write') : $this->pdo->commit();
            return new DataviewDescriptorRevision($scope, $view, $revision, $descriptor, $hash);
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                if ($nested) {
                    $this->pdo->exec('ROLLBACK TO SAVEPOINT larena_dataview_descriptor_write');
                    $this->pdo->exec('RELEASE SAVEPOINT larena_dataview_descriptor_write');
                } else {
                    $this->pdo->rollBack();
                }
            }
            if ($exception instanceof InvalidArgumentException) {
                throw $exception;
            }
            throw new InvalidArgumentException('dataview_descriptor_persistence_failed');
        }
    }

    private function hydrate(string $scope, string $view, int $revision, string $json, string $hash): DataviewDescriptorRevision
    {
        try {
            $descriptor = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('dataview_descriptor_persisted_json_invalid');
        }
        if (!is_array($descriptor) || array_is_list($descriptor)) {
            throw new InvalidArgumentException('dataview_descriptor_persisted_json_invalid');
        }
        $descriptor = $this->normalizer->normalize($descriptor);
        if ($descriptor['scope_ref'] !== $scope || $descriptor['view_id'] !== $view || !hash_equals($this->normalizer->hash($descriptor), $hash)) {
            throw new InvalidArgumentException('dataview_descriptor_persisted_integrity_failed');
        }
        return new DataviewDescriptorRevision($scope, $view, $revision, $descriptor, $hash);
    }
}
