<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('admin:reset-password {login? : Admin login name} {--password= : New password}', function (?string $login = null) {
    $adminQuery = User::query()
        ->where('is_admin', true)
        ->orderBy('id');

    if ($login !== null && trim($login) !== '') {
        $user = (clone $adminQuery)
            ->where('name', $login)
            ->first();

        if (! $user) {
            $this->error('No matching admin account was found.');

            return 1;
        }
    } else {
        $admins = $adminQuery->get(['id', 'name']);

        if ($admins->isEmpty()) {
            $this->error('There are no admin accounts in this project.');

            return 1;
        }

        if ($admins->count() === 1) {
            $user = User::find($admins->first()->id);
            $this->line('Auto-selected the only admin account: #'.$user->id.' '.$user->name);
        } else {
            $options = $admins->map(fn (User $admin) => sprintf(
                '#%d %s',
                $admin->id,
                $admin->name ?: 'Unnamed admin'
            ))->values();

            $selected = $this->choice('Select the admin account to reset', $options->all());
            $selectedId = (int) str($selected)->after('#')->before(' ')->value();
            $user = User::find($selectedId);
        }
    }

    if (! $user) {
        $this->error('The admin account does not exist.');

        return 1;
    }

    $password = $this->option('password');

    if (! is_string($password) || trim($password) === '') {
        $password = $this->secret('Enter the new password (at least 8 characters)');

        if (! is_string($password) || $password === '') {
            $this->error('The new password cannot be empty.');

            return 1;
        }

        $confirmedPassword = $this->secret('Confirm the new password');

        if ($password !== $confirmedPassword) {
            $this->error('The two passwords do not match.');

            return 1;
        }
    }

    if (mb_strlen($password) < 8) {
        $this->error('The new password must be at least 8 characters.');

        return 1;
    }

    $user->password = $password;
    $user->save();

    $this->info(sprintf(
        'The password for admin account [%s] has been reset successfully.',
        $user->name ?: 'Unnamed admin'
    ));
})->purpose('Reset an admin password from the server terminal');
