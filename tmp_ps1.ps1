$lines = Get-Content app/Http/Controllers/ExportarAsistencia.php
$start = 0
for($i=0; $i -lt $lines.Length; $i++){
  if($lines[$i] -match "\$b > 0\) \{") { $start = $i; break }
}
if($start -gt 0){
  for($j=$start; $j -lt [Math]::Min($start+130, $lines.Length); $j++){
    '{0,4}: {1}' -f ($j+1), $lines[$j]
  }
}
