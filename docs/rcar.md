SISTEMA CABAL 





# **Anexo 3 - Respuesta de Carga de tarjetas** 

<u>Nombre:          RCAR Longitud:</u> 80 BYTES <u>Formato:          FIJO</u> 

|DESDE|HASTA|DESCRIPCION|BYTE|TIPO|
|---|---|---|---|---|
|||RegistroHeader|||
|001|004|"TE04"|4|X|
|005|007|Entidad emisora (EEE)|3|N-0|
|008|010|Sucursalemisora– Fijo“000”|3|N-0|
|011|026|Todos ceros|16|N-0|
|027|032|Fecha de proceso original(DDMMAA)|6|N-0|
|033|038|Hora de proceso original(HHMMSS)|6|N-0|
|039|077|Filler|39|X|
|078|080|Código deError(Anexo11)|3|N-0|
|||RegistroDetalle|||
|001|004|"TE04"|4|X|
|005|010|Entidad emisora (EEESSS)|6|N-0|
|011|026|Número de Cuenta|16|N-0|
|027|028|Código de operación<br> 63=Crédito|2|N-0|
|029|030|Sub.Código de operación(Cumplimentarcon ‘39’)|2|N-0|
|031|039|Importe para compras|9|N-2|
|040|048|Importe pararetiros porcajeros (Informarceros)|9|N-2|
|049|054|Motivo de carga (Código y subcódigo de operación,<br>cumplimentarconceros alaizquierda)|6|N|
|055|062|Número de carga|8|N|
|063|077|Filler|15|X|
|078|080|Código deError(Anexo11)|3|N-0|
|||RegistroTrailer|||
|001|004|"TE04"|4|X|
|005|007|Entidad emisora (EEE)|3|N-0|
|008|010|Sucursalemisora– Fijo“000”|3|N-0|
|011|026|Todosnueves|16|N-0|
|027|031|Cantidad deregistros de detalle enviados|5|N-0|
|032|041|Sumatoria de los importes para compras|10|N-2|
|042|051|Sumatoria de los importes para cajeros|10|N-2|
|052|056|Cantidad deregistros calculados|5|N-0|
|057|066|Sumatoria delosimportes para compras calculados|10|N-2|
|067|076|Sumatoria delosimportes para cajeros|10|N-2|
|077|077|Filler|1|X|
|078|080|Código deError(Anexo11)|3|N-0|



