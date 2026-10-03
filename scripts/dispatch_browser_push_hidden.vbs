Option Explicit
Dim shell, files, projectPath, phpPath, workerPath, exitCode
Set shell = CreateObject("WScript.Shell")
Set files = CreateObject("Scripting.FileSystemObject")
projectPath = files.GetParentFolderName(files.GetParentFolderName(WScript.ScriptFullName))
phpPath = "C:\xampp\php\php.exe"
workerPath = files.BuildPath(projectPath, "scripts\dispatch_browser_push.php")
If Not files.FileExists(phpPath) Or Not files.FileExists(workerPath) Then
    WScript.Quit 2
End If
shell.CurrentDirectory = projectPath
On Error Resume Next
exitCode = shell.Run(Chr(34) & phpPath & Chr(34) & " " & Chr(34) & workerPath & Chr(34), 0, True)
If Err.Number <> 0 Then
    WScript.Quit 2
End If
On Error GoTo 0
WScript.Quit exitCode
