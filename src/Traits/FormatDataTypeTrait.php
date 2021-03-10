<?php

namespace VDVT\History\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use VDVT\Support\Traits\UserTimezoneTrait;

trait FormatDataTypeTrait
{
    use UserTimezoneTrait;

    /**
     * @var string
     */
    protected $displayEmpty = '<empty>';

    /**
     * @var Array
     */
    protected $displayAttributes = [
        'name' => 'Name',
    ];

    /**
     * [$relationShipAttributes ]
     * @example 'column_name' => [
     *      'mapTable'  => 'table_name_here',
     *      'mapColumn' => 'column_name_here',
     *      'mapResult' => 'column_result_name_here',
     *      'mapSelect' => 'column_select_name_here',
     *  ]
     *
     * @var Array
     */
    protected $relationShipAttributes = [];

    /**
     * @var Array
     */
    protected $ignoreFormatAttributes = [];

    /**
     * @var Array
     */
    protected $numericAttributes = [];

    /**
     * @var Array
     */
    protected $encryptAttributes = [];

    /**
     * @var Array
     */
    protected $zipcodeAttributes = [];

    /**
     * @var Array
     */
    protected $mediaAttributes = [];

    /**
     * [$currencyAttributes]
     * @var Array
     */
    protected $currencyAttributes = [];

    /**
     * @var Array
     */
    protected $typeDateTime = ['datetime', 'date'];

    /**
     * @var Array
     */
    protected $typeBoolean = ['boolean'];

    /**
     * @var Array
     */
    protected $percentAttributes = [];

    /**
     * [formatDateTimeType ]
     * @param  mixed $value
     * @param  string $formatTime
     * @return mixed
     */
    protected function historyFormatDateTime($value, $formatTime = 'm/d/Y')
    {
        if ($value) {
            $timezone = $this->getDefaultTimezone();

            // If this value is an integer, we will assume it is a UNIX timestamp's value
            // and format a Carbon object from this timestamp. This allows flexibility
            // when defining your date fields as they might be UNIX timestamps here.
            if (is_numeric($value)) {
                $dt = Carbon::createFromTimestamp($value, $timezone);
                return $dt->format($formatTime);
            }

            // If the value is in simply year, month, day format, we will instantiate the
            // Carbon instances from that format. Again, this provides for simple date
            // fields on the database, while still supporting Carbonized conversion.
            elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
                $dt = Carbon::createFromFormat('Y-m-d', $value, $timezone)->startOfDay();
                return $dt->format($formatTime);
            }

            // If the value is in simply year, month, day format, we will instantiate the
            // Carbon instances from that format. Again, this provides for simple date
            // fields on the database, while still supporting Carbonized conversion.
            elseif (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value)) {
                $dt = Carbon::createFromFormat('m/d/Y', $value, $timezone)->startOfDay();
                return $dt->format($formatTime);
            }

            // If the value is in less simply year, month, day, hours, minute format, we will instantiate the
            // Carbon instances from that format. Again, this provides for simple date
            // fields on the database, while still supporting Carbonized conversion.
            elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})$/', $value)) {
                $dt = Carbon::createFromFormat('Y-m-d H:i', $value, $timezone);
                return $dt->format($formatTime);
            }

            // Finally, we will just assume this date is in the format used by default on
            // the database connection and use that format to create the Carbon object
            // that is returned back out to the developers after we convert it here.
            elseif (!$value instanceof DateTime) {
                $format = $this->getDateFormat();
                $dt = Carbon::createFromFormat($format, $value, $timezone);
                return $dt->format($formatTime);
            }
            return Carbon::instance($value)->format($formatTime);
        }
    }

    /**
     * @param  mixed $value
     * @return mixed
     */
    protected function historyFormatNumeric($value)
    {
        if (!is_null($value)) {
            return $value ? number_format($value, 2, ',', '.') : $value;
        }
    }

    /**
     * @param  mixed $value
     * @return mixed
     */
    protected function historyFormatBoolean($attribute, $value)
    {
        if (!is_null($value)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);

            if ($configAttribute = array_get(
                property_exists($this, 'logBooleanAttributes') ? $this->logBooleanAttributes : [],
                $attribute)
            ) {
                $value = $value ? current($configAttribute) : end($configAttribute);
            }

            return $value;
        }
    }

    /**
     * @param  mixed $attribute
     * @param  mixed $value
     * @return mixed
     */
    protected function historyFormatEncryptField($value)
    {
        if (!is_null($value)) {
            return (strlen($value) < 4) ? $value : '******' . substr($value, -4);
        }
    }

    /**
     * @param  mixed $attribute
     * @param  mixed $value
     * @return mixed
     */
    protected function historyFormatFileMediaField($value)
    {
        if (!is_null($value)) {
            return end(...explode('/', $value));
        }
    }

    /**
     * @param  mixed $attribute
     * @param  mixed $value
     * @return mixed
     */
    protected function historyFormatCurrency($value)
    {
        if (!is_null($value)) {
            $formatted = "$" . number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $value)), 2, '.', ',');
            return $value < 0 ? "({$formatted})" : "{$formatted}";
        }
    }

    /**
     * @param  mixed $attribute
     * @param  mixed $value
     * @return mixed
     */
    protected function historyFormatPercent($value)
    {
        if (!is_null($value)) {
            $formatted = number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $$value)), 2, '.', ',') . '%';
            return $$value < 0 ? "({$formatted})" : "{$formatted}";
        }
    }

    /**
     * [historyFormatRelationShip ]
     * @param  mixed $value
     * @return mixed
     */
    protected function historyFormatRelationShip($attribute, $value)
    {
        $configMapping = $this->relationShipAttributes[$attribute];

        if (!is_null($value) && is_array($configMapping)) {
            $element = null;
            $value = (int) $value;
            $mapTable = array_get($configMapping, 'mapTable');
            if (class_exists($mapTable)) {
                if (($model = (new $mapTable)) instanceof Model) {
                    $element = $model->select($configMapping['mapSelect'])
                        ->where($configMapping['mapColumn'], $value)
                        ->first();
                }
            } elseif ($mapTable) {
                $element = DB::table($configMapping['mapTable'])
                    ->select($configMapping['mapSelect'])
                    ->where($configMapping['mapColumn'], $value)
                    ->first();
            }

            return $element ? $element->{$configMapping['mapResult']} : null;
        }
    }

    /**
     * @param  mixed $attribute
     * @param  mixed $origin
     * @param  mixed $current
     * @return mixed
     */
    protected function formatAttributeWithType($attribute, $origin, $current): array
    {
        $columnType = $this->getColumnAttributeType($attribute);

        if (in_array($columnType, $this->typeDateTime)) {
            $origin = $this->historyFormatDateTime($origin);
            $current = $this->historyFormatDateTime($current);
        } elseif (in_array($columnType, $this->typeBoolean)) {
            $current = $this->historyFormatBoolean($attribute, $current);
            $origin = $this->historyFormatBoolean($attribute, $origin);
        } else {
            $origin = !!$origin ? $origin : null;
            $current = !!$current ? $current : null;
        }

        return [$origin, $current, $columnType];
    }

    /**
     * @param  mixed $attribute
     * @return string
     */
    protected function getColumnAttributeType($attribute): string
    {
        return Schema::getColumnType($this->getTable(), $attribute);
    }

    /**
     * @param  mixed $attribute
     * @param  mixed $origin
     * @param  mixed $current
     * @return array
     */
    public function getHistoryDisplayValueAttribute($attribute, $origin, $current): array
    {
        list($origin, $current, $columnType) = $this->formatAttributeWithType($attribute, $origin, $current);

        // Check overide data
        $callback = 'getHistoryDisplayValue' . Str::studly($attribute) . 'Attribute';

        if (method_exists($this, $callback)) {
            list($origin, $current) = call_user_func_array([$this, $callback], [$origin, $current]);
        } else {
            // Format result if the value is a relation with other table
            if ($this->relationShipAttributes[$attribute] ?? false) {
                $origin = $this->historyFormatRelationShip($attribute, $origin);
                $current = $this->historyFormatRelationShip($attribute, $current);
            }
            // Format result if the value is a numeric.
            elseif (in_array($attribute, $this->numericAttributes)) {
                $origin = $this->historyFormatNumeric($origin);
                $current = $this->historyFormatNumeric($current);
            }
            // In case encrypt data
            elseif (in_array($attribute, $this->encryptAttributes)) {
                $origin = $this->historyFormatEncryptField($origin);
                $current = $this->historyFormatEncryptField($current);
            }
            // In case zipcode data
            elseif (in_array($attribute, $this->zipcodeAttributes)) {
                $origin = $this->historyFormatZipcodeField($origin);
                $current = $this->historyFormatZipcodeField($current);
            }
            // In case zipcode data
            elseif (in_array($attribute, $this->mediaAttributes)) {
                $origin = $this->historyFormatFileMediaField($origin);
                $current = $this->historyFormatFileMediaField($current);
            } elseif (in_array($attribute, $this->currencyAttributes)) {
                $origin = $this->historyFormatCurrency($origin);
                $current = $this->historyFormatCurrency($current);
            } elseif (in_array($attribute, $this->percentAttributes)) {
                $origin = $this->historyFormatPercent($origin);
                $current = $this->historyFormatPercent($current);
            }
        }

        // In case Datetime
        if (!in_array($attribute, $this->ignoreFormatAttributes)) {
            $origin = is_null($origin) ? $this->displayEmpty : $origin;
            $current = is_null($current) ? $this->displayEmpty : $current;
        }

        return [$origin, $current, $columnType];
    }
}
