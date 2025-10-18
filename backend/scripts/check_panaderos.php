<?php
foreach (App\Models\User::all() as $u) {
    $has = $u->roles->pluck('name')->contains('panadero') ? 'SI' : 'NO';
    echo "{$u->id} | {$u->email} | role_col={$u->role} | pivot_panadero={$has}\n";
}
