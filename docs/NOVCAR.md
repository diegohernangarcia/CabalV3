SISTEMA CABAL 





# **Anexo 3 - Novedad de Carga de tarjetas** 

<u>Nombre:</u> NOVCAR <u>Longitud:</u> 80 BYTES <u>Formato:</u> FIJO 

|DESDE|HAST|A<br>DESCRIPCION|BYTE|TIPO|
|---|---|---|---|---|
|||RegistroHeader|||
|001|004|"TE04"|4|X|
|005|007|Entidad emisora (EEE)|3|N-0|
|008|010|Sucursalemisora– Fijo“000”|3|N-0|
|011|026|Todos ceros|16|N-0|
|027|032|Fecha de proceso (DDMMAA)|6|N-0|
|033|038|Hora de proceso (HHMMSS)|6|N-0|
|039|080|Filler|42|X|
|||RegistroDetalle|||
|001|004|"TE04"|4|X|
|005|010|Entidad emisora (EEESSS)|6|N-0|
|011|026|Número de Cuenta|16|N-0|
|027|028|Código de operación<br> 63=Crédito|2|N-0|
|029|030|Sub.Código de operación(Cumplimentarcon ‘39’)|2|N-0|
|031|039|Importe para compras|9|N-2|
|040|048|Importe pararetiros porcajeros|9|N-2|
|049|054|Motivo de carga (Código y subcódigo de operación,<br>cumplimentarconceros alaizquierda)|6|N|
|055|062|Número de carga|8|N|
|063|080|Filler|18|X|
|||RegistroTrailer|||
|001|004|"TE04"|4|X|
|005|007|Entidad emisora (EEE)|3|N-0|
|008|010|Sucursalemisora– Fijo“000”|3|N-0|
|011|026|Todosnueves|16|N-0|
|027|031|Cantidad deregistros de detalle enviados|5|N-0|
|032|041|Sumatoria delosimportes para compras|10|N-2|
|042|051|Sumatoria delosimportes para cajeros|10|N-2|
|052|080|Filler|29|X|
|X: ALF<br>B: BIN|ANUME<br>ARIO|RICO<br>N : NUMERICO<br>P: EM<br>Z : ZONEADO|PAQUET|ADO|

---

### **Normas Obligatorias de Formato de Archivo (Entidad 782)**

1. **Longitud Fija por Registro:** Exactamente 80 bytes por registro (Header, Detalles y Trailer).
2. **Formato de Saltos de Línea (CRLF):** Todos los registros deben finalizar obligatoriamente con el salto de línea estilo Windows **CRLF (`\r\n` / Hex: `0x0D 0x0A`)**. Queda terminantemente prohibido el uso de saltos UNIX (`\n`) o Mac (`\r`).
3. **Terminación del Archivo en Salto de Línea:** El archivo debe concluir obligatoriamente con un salto de línea Windows CRLF (`\r\n`) al finalizar el registro Trailer (Cierre). No debe concluir directamente en texto/datos sin salto de línea.
4. **Codificación de Caracteres:** Texto plano ANSI / ISO-8859-1 (sin BOM).
