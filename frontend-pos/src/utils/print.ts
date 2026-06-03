/**
 * Helper to print receipts.
 * For true "silent" printing without a dialog window, the browser must be 
 * launched with the `--kiosk-printing` flag.
 * Example (Chrome): "C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk-printing
 */
export const printReceipt = (contentHtml: string) => {
  const iframe = document.createElement('iframe')
  iframe.style.position = 'fixed'
  iframe.style.right = '0'
  iframe.style.bottom = '0'
  iframe.style.width = '0'
  iframe.style.height = '0'
  iframe.style.border = '0'
  
  document.body.appendChild(iframe)
  
  const doc = iframe.contentWindow?.document
  if (doc) {
    doc.open()
    doc.write(`
      <html>
        <head>
          <style>
            @page { margin: 0; }
            body { 
              font-family: 'Courier New', Courier, monospace; 
              font-size: 12px; 
              width: 58mm; /* Standard thermal width */
              padding: 5mm;
            }
          </style>
        </head>
        <body onload="window.print();">
          ${contentHtml}
        </body>
      </html>
    `)
    doc.close()
    
    // Remote iframe after printing (give it some time to finish)
    setTimeout(() => {
        document.body.removeChild(iframe)
    }, 2000)
  }
}
