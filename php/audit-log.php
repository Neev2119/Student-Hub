<?php
function studentHubWriteAuditLog($connection, $action, $entityType, $entityId, $details = null)
{
    $actor = $_SESSION['student'] ?? null;
    if (!is_array($actor) || !isset($actor['id'], $actor['email'])) {
        throw new RuntimeException('An authenticated actor is required to write an audit log.');
    }

    $encodedDetails = $details === null ? null : json_encode($details, JSON_UNESCAPED_SLASHES);
    if ($details !== null && $encodedDetails === false) {
        throw new RuntimeException('Audit log details could not be encoded.');
    }

    $actorId = (string) $actor['id'];
    $actorEmail = (string) $actor['email'];
    $entityId = $entityId === null ? null : (string) $entityId;
    $statement = $connection->prepare(
        'INSERT INTO audit_logs (actor_id, actor_email, action, entity_type, entity_id, details) '
        . 'VALUES (?, ?, ?, ?, ?, ?)'
    );
    $statement->bind_param('ssssss', $actorId, $actorEmail, $action, $entityType, $entityId, $encodedDetails);
    $statement->execute();
    $statement->close();
}
