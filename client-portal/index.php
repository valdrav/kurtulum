<?php

require __DIR__.'/includes/bootstrap.php';

if (Auth::user()) {
    redirect('dashboard.php');
}

redirect('login.php');
