<?php
namespace app\components\ai;

/** A Claude call that did not happen or did not finish, with a message fit for the screen. */
class AiException extends \RuntimeException
{
    /**
     * The code of a call that was never made or was refused for a reason
     * another model would not change: no key, no library, no usage table, the
     * month's budget reached, the key refused. Retrying on another model is
     * pointless; every other failure may be worth one.
     */
    public const BLOCKED = 1;

    public function isBlocked()
    {
        return $this->getCode() === self::BLOCKED;
    }
}
