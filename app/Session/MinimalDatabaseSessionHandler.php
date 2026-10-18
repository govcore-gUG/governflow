<?php

namespace App\Session;

use Illuminate\Session\DatabaseSessionHandler;

/**
 * Database session handler that stores only id, user_id, payload and
 * last_activity. The framework handler additionally writes the IP address
 * and the user agent, which this application does not need (data
 * minimisation); the sessions table has no columns for them.
 */
class MinimalDatabaseSessionHandler extends DatabaseSessionHandler
{
    /**
     * Do not add IP address or user agent to the session record.
     *
     * @param  array<string, mixed>  $payload
     * @return $this
     */
    protected function addRequestInformation(&$payload)
    {
        return $this;
    }
}
