<?php
// core/functions.php

if (!function_exists('checkRateLimit')) {
    function checkRateLimit($pdo, $ip, $action, $limit, $minutes) {
        // දැනට error එක නැති වෙන්න මෙතනින් true යවමු.
        // ඔයාට ඇත්තටම rate limit කරන්න ඕනෙ නම් පස්සේ මේකට logic එකක් ලියමු.
        return true; 
    }
}

if (!function_exists('clearRateLimit')) {
    function clearRateLimit($pdo, $ip, $action) {
        return true;
    }
}
