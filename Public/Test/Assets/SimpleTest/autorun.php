<?php
/**
 *  Autorunner which runs all tests cases found in a file
 *  that includes this module.
 *  @package    SimpleTest
 *  @version    $Id: autorun.php 1846 2008-12-26 00:12:00Z jsweat $
 */

/**#@+
 * include simpletest files
 */
require_once dirname(__FILE__) . '/unit_tester.php';
require_once dirname(__FILE__) . '/mock_objects.php';
require_once dirname(__FILE__) . '/collector.php';
require_once dirname(__FILE__) . '/default_reporter.php';
/**#@-*/

$GLOBALS['SIMPLETEST_AUTORUNNER_INITIAL_CLASSES'] = get_declared_classes();
register_shutdown_function('simpletest_autorun');

/**
 *    Exit handler to run all recent test cases if no test has
 *    so far been run. Uses the DefaultReporter which can have
 *    it's output controlled with SimpleTest::prefer().
 */
function simpletest_autorun() {
    try {
        if (tests_have_run()) {
            return;
        }
        $candidates = capture_new_classes();
        $loader = new SimpleFileLoader();
        $suite = $loader->createSuiteFromClasses(
                basename(initial_file()),
                $loader->selectRunnableTests($candidates));
        $result = $suite->run(new StyxReporter());
    } catch (Exception $stack_frame_fix) {
        print $stack_frame_fix->getMessage();
        $result = false;
    }
    if (SimpleReporter::inCli()) {
        exit($result ? 0 : 1);
    }
}

/**
 *    Checks the current test context to see if a test has
 *    ever been run.
 *    @return boolean        True if tests have run.
 */
function tests_have_run() {
    if ($context = SimpleTest::getContext()) {
        return (boolean)$context->getTest();
    }
    return false;
}

/**
 *    The first autorun file.
 *    @return string        Filename of first autorun script.
 */
function initial_file() {
    static $file = false;
    if (! $file) {
        if (isset($_SERVER, $_SERVER['SCRIPT_FILENAME'])) {
            $file = $_SERVER['SCRIPT_FILENAME'];
        } else {
	        $included_files = get_included_files();
	        $file = reset($included_files);
        }
    }
    return $file;
}

/**
 *    Every class since the first autorun include. This
 *    is safe enough if require_once() is always used.
 *    @return array        Class names.
 */
function capture_new_classes() {
    global $SIMPLETEST_AUTORUNNER_INITIAL_CLASSES;
    return array_map('strtolower', array_diff(get_declared_classes(),
                            $SIMPLETEST_AUTORUNNER_INITIAL_CLASSES ?
                            $SIMPLETEST_AUTORUNNER_INITIAL_CLASSES : array()));
}
?>