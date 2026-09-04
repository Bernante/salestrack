<?php
/**
 * Error Handler Bootstrap
 * Prevents raw PHP errors from displaying to users in production
 * All errors logged to file only
 */

// Set error display OFF - prevent raw stack traces from showing to users
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Enable error logging to file (server-dependent, but recommended)
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Set error reporting to catch all errors
error_reporting(E_ALL);

/**
 * Global Exception Handler
 * Catches ANY uncaught exception and shows safe error message
 */
set_exception_handler(function(Throwable $exception) {
    // Log full exception details to file (developers only)
    error_log('EXCEPTION: ' . $exception->getMessage());
    error_log('File: ' . $exception->getFile() . ' Line: ' . $exception->getLine());
    error_log('Stack Trace: ' . $exception->getTraceAsString());
    
    // Show safe error message to user (no file paths, no code)
    $_SESSION['flash_error'] = 'An unexpected error occurred. Please try again or contact support.';
    
    // Redirect to safe page
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/actions/') !== false) {
        // If in an action handler, redirect to login
        header('Location: /login.php');
    } else {
        // Otherwise stay on current page (will show flash error)
        header('Refresh: 0');
    }
    exit;
});

/**
 * Global Error Handler
 * Catches PHP errors (warnings, notices, etc.) and treats them as exceptions
 */
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Don't handle @ suppressed errors
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // Log error to file
    error_log("PHP Error [$errno]: $errstr in $errfile:$errline");
    
    // For fatal errors, show safe message to user
    if ($errno === E_ERROR || $errno === E_PARSE || $errno === E_CORE_ERROR || $errno === E_COMPILE_ERROR) {
        $_SESSION['flash_error'] = 'A critical error occurred. Please contact support.';
        header('Location: /login.php');
        exit;
    }
    
    // For warnings/notices, continue execution
    return true;
});

/**
 * Shutdown Handler
 * Catches fatal errors at shutdown
 */
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('FATAL ERROR at shutdown: ' . $error['message']);
        header('Location: /login.php');
        exit;
    }
});
