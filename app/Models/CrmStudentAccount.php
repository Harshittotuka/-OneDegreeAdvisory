<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * An enrolled student's sign-in to their journey planner. Created by the
 * counsellor from the CRM when they start the planner; the password they are
 * given is temporary until the student sets their own.
 */
class CrmStudentAccount extends Model
{
    protected $fillable = ['crm_lead_id', 'email', 'password', 'admin_password', 'must_change_password', 'is_active', 'last_login_at', 'password_changed_at', 'created_by'];

    protected $hidden = ['password', 'admin_password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            // Readable by counsellors, so encrypted with the app key rather than hashed.
            'admin_password' => 'encrypted',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    /**
     * A temporary password a counsellor can read out or paste into a message:
     * letters and digits only, with the look-alike characters left out.
     */
    public static function temporaryPassword(int $length = 10): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $password = '';
            for ($i = 0; $i < $length; $i++) {
                $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (! preg_match('/\d/', $password) || ! preg_match('/[a-z]/', $password) || ! preg_match('/[A-Z]/', $password));

        return $password;
    }

    /**
     * The counsellor-held admin password: it always signs in to this student's
     * portal, whatever the student's own password is. Made the first time it
     * is asked for, so accounts created before it existed get one too.
     */
    public function adminPassword(): string
    {
        if (! $this->admin_password) {
            $this->forceFill(['admin_password' => self::temporaryPassword(14)])->save();
        }

        return $this->admin_password;
    }

    /** Whether the given text is this account's admin password. */
    public function isAdminPassword(string $given): bool
    {
        return $this->admin_password !== null && $this->admin_password !== '' && hash_equals((string) $this->admin_password, $given);
    }

    public static function normaliseEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    /** Signed-in use needs an active account on a record that is still an enrolled student. */
    public function canSignIn(): bool
    {
        return $this->is_active && $this->lead && $this->lead->is_student && ! $this->lead->trashed();
    }
}
