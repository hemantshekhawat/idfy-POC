<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class idfyAadharOcr extends Model
{

    public $fillable = ["task_id", "group_id", "doc_url", "aadhaar_consent", "request_data", "idfy_request_id",
        "status", "aadhaar_number", "gender", "is_scanned", "name_on_card", "raw_text", "year_of_birth", "response_data",
    ];

    /**
     * idfyAadharOcr constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return array
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * @param array $fillable
     */
    public function setFillable(array $fillable): void
    {
        $this->fillable = $fillable;
    }

    /**
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * @param string $connection
     */
    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param string $table
     */
    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    /**
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * @param string $primaryKey
     */
    public function setPrimaryKey(string $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public static function getDispatcher(): \Illuminate\Contracts\Events\Dispatcher
    {
        return self::$dispatcher;
    }

    /**
     * @param \Illuminate\Contracts\Events\Dispatcher $dispatcher
     */
    public static function setDispatcher(\Illuminate\Contracts\Events\Dispatcher $dispatcher): void
    {
        self::$dispatcher = $dispatcher;
    }

}
