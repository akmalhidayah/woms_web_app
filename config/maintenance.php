<?php

return [
    'quick_scan_interval_minutes' => 30,
    'deep_scan_time' => '01:30',
    'approval_aging_hours' => 48,
    'chunk_size' => 200,
    'deep_scan_chunk_size' => 100,
    'max_findings_per_category' => 100,
    'quick_snapshot_ttl_minutes' => 120,
    'deep_snapshot_ttl_minutes' => 1440,
    'deep_scan_context_ttl_minutes' => 30,
    'scheduler_heartbeat_ttl_minutes' => 15,
    'quick_scan_lock_seconds' => 180,
    'deep_scan_lock_seconds' => 1800,
    'quick_scan_warning_duration_ms' => 5000,
    'deep_scan_step_warning_duration_ms' => 8000,
    'job_timeout_seconds' => 1200,
    'lock_seconds' => 1800,
];
