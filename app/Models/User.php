<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['first_name', 'last_name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * The frontend's design ships a handful of placeholder headshots but no
     * avatar-upload feature is in scope — this just spreads users
     * deterministically across that existing pool instead of every user
     * rendering with the same picture.
     */
    public function avatarUrl(): string
    {
        static $pool = [
            '/assets/images/Avatar.png',
            '/assets/images/chat_profile.png',
            '/assets/images/chat_profile1.png',
            '/assets/images/people1.png',
            '/assets/images/people2.png',
            '/assets/images/people3.png',
            '/assets/images/profile.png',
        ];

        return $pool[$this->id % count($pool)];
    }
}
