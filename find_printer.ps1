Get-PnpDevice -PresentOnly | Select-Object Status, FriendlyName, Class, InstanceId | ConvertTo-Json
Get-Printer | Select-Object Name, PrinterStatus, WorkOffline, PortName, Default | ConvertTo-Json
Get-CimInstance Win32_Printer | Select-Object Name, Default, PrinterStatus, WorkOffline | ConvertTo-Json
