<?php

namespace App\Services\Account;

use App\Models\User;
use App\Services\Uploads\UploadManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProfileService
{
    public function __construct(private readonly UploadManager $uploadManager)
    {
    }

    public function update(User $user, array $payload, ?UploadedFile $avatar = null): User
    {
        $user->name = $payload['name'];
        $user->date_of_birth = $payload['date_of_birth'] ?? null;

        if (!empty($payload['password'])) {
            $user->password = Hash::make($payload['password']);
        }

        if ($avatar) {
            $avatarId = $this->storeAvatar($user, $avatar);

            if ($avatarId) {
                $user->avatar = $avatarId;
            }
        }

        $user->save();

        return $user->fresh();
    }

    private function storeAvatar(User $user, UploadedFile $avatar): ?int
    {
        if (!Schema::hasTable('uploads')) {
            return null;
        }

        $upload = $this->uploadManager->store($avatar, (int) $user->id);

        return (int) $upload->id;
    }
}
