<#
.SYNOPSIS
  Install the XenForo add-on development reference as a Claude Code skill (Windows).

.DESCRIPTION
  Copies the reference (SKILL.md + xenforo.md + docs\ + cheatsheets\ + examples\)
  into a self-contained skill directory that Claude Code auto-loads.

.PARAMETER Scope
  'User'    -> install into $HOME\.claude\skills (default)
  'Project' -> install into <ProjectDir>\.claude\skills

.PARAMETER ProjectDir
  Target project directory when -Scope Project. Defaults to the current directory.

.EXAMPLE
  .\install.ps1
  Installs the skill for the current user.

.EXAMPLE
  .\install.ps1 -Scope Project -ProjectDir C:\dev\my-xenforo-addon
  Installs the skill into that project's .claude\skills directory.
#>
[CmdletBinding()]
param(
  [ValidateSet('User', 'Project')]
  [string]$Scope = 'User',
  [string]$ProjectDir = '.'
)

$ErrorActionPreference = 'Stop'
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$SkillName = 'xenforo-addon-dev'

if ($Scope -eq 'User') {
  $Dest = Join-Path $HOME ".claude\skills\$SkillName"
}
else {
  if (-not (Test-Path $ProjectDir)) {
    throw "Project directory does not exist: $ProjectDir"
  }
  $Dest = Join-Path (Resolve-Path $ProjectDir) ".claude\skills\$SkillName"
}

Write-Host "Installing XenForo reference skill -> $Dest"
if (Test-Path $Dest) { Remove-Item $Dest -Recurse -Force }
New-Item -ItemType Directory -Force -Path $Dest | Out-Null

Copy-Item (Join-Path $ScriptDir "skills\$SkillName\SKILL.md") (Join-Path $Dest 'SKILL.md') -Force
Copy-Item (Join-Path $ScriptDir 'xenforo.md')                (Join-Path $Dest 'xenforo.md') -Force
Copy-Item (Join-Path $ScriptDir 'docs')                      $Dest -Recurse -Force
Copy-Item (Join-Path $ScriptDir 'cheatsheets')               $Dest -Recurse -Force
Copy-Item (Join-Path $ScriptDir 'examples')                  $Dest -Recurse -Force

Write-Host "Done. Skill installed to $Dest"
Write-Host "Restart Claude Code (or run /context) so it picks up the new skill."
