<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This is a framework for a model-view-controller (MVC) architecture.
    The actual control systems are driven by public_html/index.php
    The work is done by "delegate" scripts in pages/
    
    Model       all data is stored in $_SESSION
    View        generated by display() in pages/xxx.php; takes no actions
    Controller  handlers in pages/xxx.php; change application state
    
{{{ Data structure of a single "page", stored in $_SESSION['pages']

    delegate        the name of a PHP script with display() and one or more
                    handlers, relative to MP_BASE_DIR/pages/
    context         an array of info for use by display()
    handlers[]      an array of arrays. First key is event ID.
                    Second keys (i.e. for each handler) are:
        funcName    the name of an event handler in the current delegate
        funcArg     some sort of argument to be passed to the handler
}}}
{{{ Division of responsibilities

    index.php VS xxx.php in pages/
        index.php is creates the framework; loads and saves the session; etc.
        xxx.php defines a display() function and zero or more event handlers.
    
    display() vs. event handlers
        display() creates an HTML pages and registers handlers for events.
        It MUST NOT change the application "state" or do any work that shouldn't
        be repeated multiple times.
        Event handlers do not create any HTML output, but are responsible for
        doing work (either themselves or by launching a background job) and for
        guiding application flow by changing the current delegate page using
        pageGoto(), pageCall(), and pageReturn().
}}}

*****************************************************************************/
// Someone else MUST have defined this before including us!
if(!defined('MP_BASE_DIR')) die("MP_BASE_DIR is not defined.");
require_once(MP_BASE_DIR.'/lib/sessions.php');

// These functions are for use in event handlers only.
#{{{ pageGoto - sets the current delegate without changing page stack depth
############################################################################
/**
* $pageName is the path to the delegate script, relative to pages/
* $context is information used by the delegate's display() function
*   to specify e.g. which model to display, etc.
* This function swaps out the current delegate for the specified one.
*/
function pageGoto($pageName, $context = null)
{
    pageReturn();                   // pops the old delegate
    pageCall($pageName, $context);  // pushes the new delegate
}
#}}}########################################################################

#{{{ pageCall - sets a new delegate as a "subroutine call" of the current one
############################################################################
/**
* Like pageGoto(), but the new delegate is pushed onto a stack above
* the old one, so it can be popped off by pageReturn().
*/
function pageCall($pageName, $context = null)
{
    $newPage = array(
        'delegate' => $pageName,
        'context' => $context,
        'handlers' => array()
    );
    $_SESSION['pages'][] = $newPage; // equiv. to array_push()
}
#}}}########################################################################

#{{{ pageReturn - returns to the delegate that called the current one
############################################################################
/**
* Undoes the actions of pageCall() and returns to the old delegate.
*/
function pageReturn()
{
    if(count($_SESSION['pages']) >= 1)
        array_pop($_SESSION['pages']);
}
#}}}########################################################################


// These functions are for use in display() only.
#{{{ makeEventURL - creates a URL for <a> tags that launches an event via GET
############################################################################
/**
* Returns a string like
*   "index.php?session=123ABC&eventID=456789"
*/
function makeEventURL($funcName, $funcArg = null)
{
    $id = addEventHandler($funcName, $funcArg);
    // What's the difference b/t this and $_SERVER[SCRIPT_NAME] ?
    return "$_SERVER[PHP_SELF]?$_SESSION[sessTag]&eventID=$id";
}
#}}}########################################################################

#{{{ makeEventForm - creates a <form> that launches an event via POST
############################################################################
/**
* Returns a string like
*   <form method='post' enctype='multipart/form-data' action='index.php'>
*   <input type='hidden' name='session' value='123ABC'>
*   <input type='hidden' name='eventID' value='456789'>
*/
function makeEventForm($funcName, $funcArg = null, $hasFileUpload = false)
{
    $id = addEventHandler($funcName, $funcArg);
    $s = "<form method='post' ";
    if($hasFileUpload) $s .= "enctype='multipart/form-data' ";
    // What's the difference b/t this and $_SERVER[SCRIPT_NAME] ?
    $s .= "action='$_SERVER[PHP_SELF]'>\n";
    $s .= postSessionID();
    $s .= "<input type='hidden' name='eventID' value='$id'>\n";
    return $s;
}
#}}}########################################################################


// These functions are utilities for the driving script (index.php)
#{{{ addEventHandler - registers a handler for the current page delegate
############################################################################
/**
* Returns the eventID for the handler.
*/
function addEventHandler($funcName, $funcArg)
{
    end($_SESSION['pages']);
    $key = key($_SESSION['pages']);
    $page =& $_SESSION['pages'][$key];
    
    $eid = $_SESSION['currEventID']++;
    if($_SESSION['currEventID'] >= 1<<30) $_SESSION['currEventID'] = 1;
    
    $page['handlers'][$eid] = array(
        'funcName' => $funcName,
        'funcArg' => $funcArg
    );
    
    return $eid;
}
#}}}########################################################################

#{{{ clearEventHandlers - removes existing handlers and reseeds the starting ID
############################################################################
/**
* Removes all registered event handlers for the current delegate.
*/
function clearEventHandlers()
{
    end($_SESSION['pages']);
    $key = key($_SESSION['pages']);
    $page =& $_SESSION['pages'][$key];
    
    $page['handlers'] = array();
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################
?>
