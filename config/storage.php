<?php

return [
    'backup_disk' => getenv('BACKUP_DISK') ?: 'local',
    'backup_path' => getenv('BACKUP_PATH') ?: __DIR__ . '/../storage/backups',
];

