<?php
// app/Services/FirebaseService.php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;

class FirebaseService
{
    protected $database;

   public function __construct()
{
    $factory = (new Factory)
        ->withServiceAccount(config('services.firebase.credentials_file'))
        ->withDatabaseUri('https://sairjayamandiri-default-rtdb.asia-southeast1.firebasedatabase.app');

    $this->database = $factory->createDatabase();
}

    public function updateMemberData($memberId, $data)
    {
        return $this->database
            ->getReference('members/' . $memberId)
            ->set($data);
    }
}
