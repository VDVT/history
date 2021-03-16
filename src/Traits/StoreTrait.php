<?php

namespace VDVT\History\Traits;

use Illuminate\Support\Facades\Auth;
use VDVT\History\Constants\References;
use VDVT\History\Events\CreatedHistory;
use VDVT\History\Events\SaveLogHistory;

trait StoreTrait
{
    use TargetTrait;

    /**
     * @return void
     */
    protected function saveLogAttribute(array $data = [], string $type)
    {
        event(new SaveLogHistory
            (
                array_merge(
                    [
                        'user_id' => Auth::id(),
                    ],
                    $this->getTargetHistory(),
                    $data
                ),
                $type
            )
        );

        if (in_array($type, [
            References::HISTORY_EVENT_CREATED,
            References::HISTORY_EVENT_DELETED,
        ])) {
            event(new CreatedHistory
                (
                    $this,
                    array_get($data, 'path'),
                    $type
                )
            );
        }
    }
}
