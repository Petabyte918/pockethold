<?php











namespace Composer\DependencyResolver;

use Composer\Util\IniHelper;
use Composer\Repository\RepositorySet;




class SolverProblemsException extends \RuntimeException
{
protected $problems;
protected $learnedPool;

public function __construct(array $problems, array $learnedPool)
{
$this->problems = $problems;
$this->learnedPool = $learnedPool;

parent::__construct('Failed resolving dependencies with '.count($problems).' problems, call getPrettyString to get formatted details', 2);
}

public function getPrettyString(RepositorySet $repositorySet, Request $request, Pool $pool, $isVerbose, $isDevExtraction = false)
{
$installedMap = $request->getPresentMap(true);
$hasExtensionProblems = false;
$isCausedByLock = false;

$problems = array();
foreach ($this->problems as $problem) {
$problems[] = $problem->getPrettyString($repositorySet, $request, $pool, $isVerbose, $installedMap, $this->learnedPool)."\n";

if (!$hasExtensionProblems && $this->hasExtensionProblems($problem->getReasons())) {
$hasExtensionProblems = true;
}

$isCausedByLock |= $problem->isCausedByLock($repositorySet, $request, $pool);
}

$i = 1;
$text = "\n";
foreach (array_unique($problems) as $problem) {
$text .= "  Problem ".($i++).$problem;
}

$hints = array();
if (!$isDevExtraction && (strpos($text, 'could not be found') || strpos($text, 'no matching package found'))) {
$hints[] = "Potential causes:\n - A typo in the package name\n - The package is not available in a stable-enough version according to your minimum-stability setting\n   see <https://getcomposer.org/doc/04-schema.md#minimum-stability> for more details.\n - It's a private package and you forgot to add a custom repository to find it\n\nRead <https://getcomposer.org/doc/articles/troubleshooting.md> for further common problems.";
}

if ($hasExtensionProblems) {
$hints[] = $this->createExtensionHint();
}

if ($isCausedByLock && !$isDevExtraction) {
$hints[] = "Use the option --with-all-dependencies to allow upgrades, downgrades and removals for packages currently locked to specific versions.";
}

if (strpos($text, 'found composer-plugin-api[2.0.0] but it does not match') && strpos($text, '- ocramius/package-versions')) {
$hints[] = "<warning>ocramius/package-versions only provides support for Composer 2 in 1.8+, which requires PHP 7.4.</warning>\nIf you can not upgrade PHP you can require <info>composer/package-versions-deprecated</info> to resolve this with PHP 7.0+.";
}


 if (!class_exists('PHPUnit\Framework\TestCase', false)) {
if (strpos($text, 'found composer-plugin-api[2.0.0] but it does not match')) {
$hints[] = "You are using a snapshot build of Composer 2, which some of your plugins seem to be incompatible with. Make sure you update your plugins or report an issue to them to ask them to support Composer 2. To work around this you can run Composer with --ignore-platform-req=composer-plugin-api, but this may result in broken plugins and bigger problems down the line.";
} else {
$hints[] = "You are using a snapshot build of Composer 2, which may be the cause of the problem. Run `composer self-update --stable` and then try again. In case it solves the problem, please report an issue mentioning Composer 2.";
}
}

if ($hints) {
$text .= "\n" . implode("\n\n", $hints);
}

return $text;
}

public function getProblems()
{
return $this->problems;
}

private function createExtensionHint()
{
$paths = IniHelper::getAll();

if (count($paths) === 1 && empty($paths[0])) {
return '';
}

$text = "To enable extensions, verify that they are enabled in your .ini files:\n    - ";
$text .= implode("\n    - ", $paths);
$text .= "\nYou can also run `php --ini` inside terminal to see which files are used by PHP in CLI mode.";

return $text;
}

private function hasExtensionProblems(array $reasonSets)
{
foreach ($reasonSets as $reasonSet) {
foreach ($reasonSet as $rule) {
if (0 === strpos($rule->getRequiredPackage(), 'ext-')) {
return true;
}
}
}

return false;
}
}
