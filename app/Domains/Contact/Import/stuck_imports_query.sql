-- Query to identify import jobs stuck in the 'processing' state for more than 30 minutes.
-- These jobs are candidates for recovery/cleanup as they may have been interrupted or failed silently.
SELECT 
    id,
    account_id,
    user_id,
    vault_id,
    filename,
    file_hash,
    total_rows,
    processed_rows,
    failed_rows,
    status,
    started_at,
    created_at,
    TIMESTAMPDIFF(MINUTE, started_at, NOW()) AS minutes_stuck
FROM 
    import_jobs
WHERE 
    status = 'processing'
    AND started_at <= NOW() - INTERVAL 30 MINUTE
ORDER BY 
    started_at ASC;
