<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

trait EncryptsId
{
    /**
     * Override the toArray method to encrypt the primary key.
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();
        $keyName = $this->getKeyName();

        if (isset($array[$keyName])) {
            $array[$keyName] = Crypt::encryptString((string) $this->getAttribute($keyName));
        }

        return $array;
    }
}
