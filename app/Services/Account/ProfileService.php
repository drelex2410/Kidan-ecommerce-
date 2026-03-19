<?php

namespace App\Services\Account;

use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProfileService
{
    public function update(User $user, array $payload, ?UploadedFile $avatar = null): User
    {
        $user->name = $payload['name'];

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

        $upload = new Upload();
        $upload->file_original_name = pathinfo($avatar->getClientOriginalName(), PATHINFO_FILENAME);
        $upload->file_name = $avatar->store('uploads/all');
        $upload->user_id = $user->id;
        $upload->extension = $avatar->getClientOriginalExtension();
        $upload->type = 'image';
        $upload->file_size = $avatar->getSize();
        $upload->save();

        return (int) $upload->id;
    }
}
