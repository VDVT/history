<?php

namespace VDVT\History\Traits;

use Illuminate\Support\Facades\Auth;
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
    }
}
