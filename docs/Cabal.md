# RULES.md — Especificaciones Técnicas y Reglas de Desarrollo Cabal
# MANUAL DE TARJETA PRECARGADA NO BANCARIA-SOCIAL



## SISTEMA CABAL – Función Emisora

1. Funcionalidad de las tarjetas.
2. Alta de tarjetas Precargadas – Emisión Inicial.
3. Procedimiento de entrega de tarjetas para la emisión inicial.
4. Generación de cargas y descargas a las tarjetas. Transferencias.
5. Alta de tarjeta Precargadas – Emisiones individuales.
6. Actualización de datos filiatorios de los beneficiarios de tarjetas precargadas.
7. Denuncia por extravío o hurto de tarjetas por parte de los beneficiarios.
8. Solicitudes de reemplazo de tarjetas por deterioro.
9. Compras en los comercios adheridos.
10. Consultas de beneficiarios sobre saldos y movimientos de compras.
11. Desconocimientos de los beneficiarios de compras en comercios.
12. Ingreso y aplicación de contracargos o devoluciones de compras.
13. Solicitud de Comercios a la operatoria de ventas del Programa.

## Generalidades

1. Crediware.
2. Transmisión de archivos.
3. IVR desarrollado para la atención telefónica de los beneficiarios.
4. Trámites atendidos en el Centro de Atención por operadores para los beneficiarios de las tarjetas precargadas.

## Anexos

1. Novedades de altas de Tarjetas Precargadas
2. Respuesta de altas de Tarjetas Precargadas
3. Novedad y respuesta de carga de Tarjetas / Novedad y respuesta de descarga
4. Modificaciones y bajas de Tarjetas Precargadas / Respuesta
5. Diseño de Registro de Transferencia de Saldos
6. Diseño de Registro Archivo de Rendición
7. Maestro de Tarjetas
8. Diagrama IVR
9. Tabla de Códigos de Provincia
10. Tabla de códigos de tipos de Documentos
11. Tabla de Códigos de error – respuesta de carga
12. Tabla de errores altas / modificaciones de usuarios
13. Tabla de causas de bloqueo
14. Archivo de llamados recibidos de los beneficiarios
15. Solicitud de Comercios a la operatoria del Programa

---

### Página 4 – Función Emisora

*(título de sección)*

---

### Página 5

## 1. Funcionalidad de las tarjetas

Las tarjetas precargadas sociales se emitirán por requerimiento de la Entidad, como así también las cargas de importes a las mismas.

Las tarjetas podrán ser utilizadas por los beneficiarios en los comercios adheridos al Plan y si la Entidad así lo permitiera, se podrán realizar retiros en efectivo por cajeros automáticos.

Para poder comprar en los comercios, el beneficiario deberá presentar su tarjeta y su documento de identidad.

Los comercios deberán solicitar autorización por todas las compras y en el momento de solicitud de la misma, se descontará del monto precargado el importe de la compra cuya autorización se está gestionando.

Las compras se autorizarán siempre que la tarjeta se encuentre vigente y que el monto de la operación sea inferior o igual al saldo remanente que registre la tarjeta.

Para realizar operaciones por cajeros automáticos, el usuario deberá contar con un Número de Identificación Personal (NIP). El NIP es el número confidencial que identifica a cada socio y le permite efectuar transacciones por Cajeros Automáticos con su tarjeta. La misma consta de cuatro dígitos.

Para solicitarlo, el beneficiario deberá comunicarse telefónicamente al teléfono dispuesto por la Entidad Emisora, que atiende las 24 horas del día, los 365 días del año. Se realizará una verificación positiva mediante la cual el operador podrá corroborar la identidad del solicitante. La clave será otorgada en el momento, y deberá ser modificada en un Cajero Automático de la red Link o Cabal, antes de las 24 hs. del día en que fue otorgada.

## 2. Alta de tarjetas Precargadas – Emisión Inicial

La Entidad solicitará a CABAL las altas de las tarjetas a través del archivo “Novedades de altas de Tarjetas Precargadas”, cuyo diseño se incluye en el Anexo 1.

El mismo deberá trasmitirse a través de FTP.

CABAL procesará el archivo durante las 72 horas hábiles siguientes a recibido el mismo y como resultado de su procesamiento generará el Archivo “Respuesta de altas y modificaciones de tarjetas precargadas”, cuyo diseño se incluye en el Anexo 2.

Los rechazos de novedades de altas que se produjeran, deberán ser enviados nuevamente a CABAL, una vez que haya sido corregido el o los datos erróneos que motivaron el rechazo. Las tarjetas se emitirán ordenadas por sucursal, teniendo en cuenta que la Entidad solicitará el alta de una sucursal ficticia por cada Centro de Gestión. Dentro de este ordenamiento se ordenarán alfabéticamente por apellido y nombre.

La vigencia de las tarjetas será de 3 años como mínimo o por el plazo de vigencia del Plan. En las emisiones iniciales, las tarjetas se emitirán bloqueadas y se procederá a su habilitación a las 48 horas hábiles de entregadas las mismas a los beneficiarios.

---

### Página 6

## 3. Procedimiento de entrega de tarjetas para la emisión inicial

El beneficiario deberá presentarse en uno de los días especificados, en el domicilio correspondiente con su Documento Nacional de Identidad, Libreta Cívica o Libreta de Enrolamiento y una fotocopia de la primera hoja y segunda hoja.

No se entregarán tarjetas a beneficiarios que no se presenten con estos elementos o bien que no coincidan con los datos informados por la Entidad, el número de documento o el Apellido y Primer Nombre.

Para proceder a la entrega, el beneficiario deberá firmar: Recibo de Tarjeta, Frente y dorso del Contrato de Tarjeta Prepaga/Precargada (Definido por la Entidad) y la banda de firma de la tarjeta.

Se confeccionará un legajo con los elementos firmados por el beneficiario y las fotocopias del documento de identidad presentados.

En cada local se encontrará presente una persona en representación de la Entidad, quien podrá responder a sus inquietudes y dudas.

Diariamente, la Entidad informará a CABAL, las tarjetas que fueron entregadas a través del archivo “Modificaciones y bajas de tarjetas precargadas” (Código de modificación 20 – Habilitación). CABAL procederá a la habilitación de las mismas. Las tarjetas quedarán habilitadas para su uso por parte de los beneficiarios a las 48 horas hábiles de haberlas recibido.

Transcurrido el tiempo de entrega de las tarjetas, estipulado por la Entidad, la misma procederá a la destrucción de los plásticos no entregados confeccionando el Acta de Destrucción correspondiente. La Entidad deberá remitir para su proceso el bloqueo definitivo de las tarjetas destruidas y, en caso de corresponder, deberá enviar un archivo de descargas para cancelar el saldo remanente.

---

## 4. Generación de cargas y descargas a las tarjetas. Transferencias

Las operaciones de cargas, descargas y transferencias de saldos requieren un control exhaustivo debido a las consecuencias que provocan, relacionadas básicamente con movimientos de fondos que afectan a los tres actores de esta operatoria: la Entidad, los beneficiarios y Cabal.

Es por ello que, con el objetivo de coordinar las transferencias de archivos y movimientos de dinero que esta operatoria requiere, se detallan a continuación los procedimientos y requisitos a seguir.

### Pautas Generales:

- Las cargas y las descargas de las tarjetas deben venir en archivos separados.
- El importe de las cargas solicitadas debe coincidir con el monto final depositado en la cuenta bancaria de Cabal.
- Las cargas que resulten rechazadas al momento del proceso generarán una devolución de fondos a la Entidad por el monto correspondiente a las mismas.
- Las descargas de saldos generarán una devolución de fondos a la Entidad.
- Las cargas y descargas simultáneas entre diferentes tarjetas son consideradas transferencias de saldos. Este archivo deberá contener las tarjetas a las que se le realiza la descarga más las tarjetas a las que se le cargará dicho importe. Las dos operaciones se identificarán por su código de operación y la sumatoria de importes del archivo será siempre cero.
- Las tarjetas a las que se le realizarán movimientos requieren una serie de controles previos para establecer su estado de aptitud para recibirlos. Estos deben ser tenidos en cuenta por la Entidad para disminuir los rechazos.

---

### Página 7

### Cargas de tarjetas

Cuando la Entidad desee realizar cargas a las tarjetas, enviará a CABAL vía FTP un único archivo con el total de las mismas, de acuerdo al diseño denominado “Carga de Tarjetas Precargadas”. Asimismo, deberá efectuar el depósito o transferencia bancaria coincidente con el importe total del archivo de cargas en la cuenta bancaria de Cabal habilitada para tal fin y enviar por fax al Departamento de Tesorería de Cabal (4891-2666) el comprobante de dicho movimiento y vía mail a tesoreria@cabal.coop, ssantamarina@cabal.com.ar y durso@cabal.com.ar.

A partir de la recepción del archivo, el mismo será sometido a una validación de consistencia en el proceso nocturno de la fecha de recepción, siempre que haya sido recibido antes de las 17 hs. Caso contrario, el proceso de validación se realizará en el proceso del día hábil siguiente. Será sometido a una primera instancia de control que consiste en verificar que la información contenida en el Trailer del archivo respecto a la cantidad de registros y a la sumatoria del importe coincida con el detalle informado en el mismo.

- Si el archivo es correcto, se notificará de su existencia al sector de Tesorería de Cabal para que éste pueda controlar el depósito o transferencia bancaria realizada por la Entidad. Según el resultado de este control, Tesorería deberá autorizar o rechazar la aplicación definitiva de las cargas.

  - Si el importe depositado en la cuenta de Cabal coincide con el informado en el archivo, Tesorería confirmará la operación y la actualización de saldos se realizará en el proceso nocturno del día en que fue aceptada.

    La autorización por parte del departamento de Tesorería de Cabal iniciará el proceso definitivo de cargas. Si como resultado de este proceso se generaran rechazos que impidieran las cargas para algunas tarjetas en particular, éstas serán informadas en el archivo “Respuesta de Carga de Tarjetas Precargadas” para que la Entidad, de considerarlo necesario, solicite una nueva carga en un plazo de 48 hs. hábiles de enviada la respuesta. Si la Entidad no enviara un nuevo archivo dentro de ese plazo, el Departamento de Tesorería generará la devolución del importe mediante transferencia bancaria.

  - Si el importe depositado en la cuenta de Cabal NO coincide con el informado en el archivo, Tesorería rechazará la operación y la actualización de saldos NO se realizará. El resultado se informará en el archivo “Respuesta de Carga de Tarjetas Precargadas” para que la Entidad, de considerarlo necesario, solicite una nueva carga en un plazo de 48 hs. hábiles. Si la Entidad no enviara un nuevo archivo dentro de ese plazo, Tesorería generará la devolución del importe mediante transferencia bancaria.

- Si el archivo no es consistente, la Entidad recibirá el archivo de respuesta con su correspondiente rechazo y deberá reemplazar el archivo dentro de las 48 hs. hábiles siguientes a la notificación de rechazo. Si la Entidad no reemplazara el mismo dentro de ese plazo, Tesorería generará la devolución del importe depositado mediante transferencia bancaria.

**Se sugiere enviar el archivo 48 horas antes del día en que se hace la transferencia o el depósito del dinero, para evitar demoras en la aplicación de las cargas.**

#### Si el importe depositado es mayor al informado:

- Enviar nuevamente el archivo de carga modificado (no uno adicional) dentro de las 48 hs. hábiles. Caso contrario, Tesorería generará la devolución del importe depositado.
- Aceptar como válido el archivo y la devolución del sobrante, luego del proceso de las cargas contenidas en el archivo inicial.

#### Si el importe depositado es menor al informado:

- Realizar a la brevedad un nuevo depósito por el importe faltante.
- Enviar un nuevo archivo de reemplazo dentro de las 48 hs. hábiles. Caso contrario, Tesorería generará la devolución del importe depositado.

---

### Página 8

La autorización por parte de Tesorería de Cabal iniciará el proceso definitivo de cargas. Si como resultado de este proceso se generaran rechazos que impidieran las cargas para algunas tarjetas en particular, éstas serán informadas en el archivo “Respuesta de Carga de Tarjetas Precargadas” para que la Entidad, de considerarlo necesario, solicite una nueva carga en un proceso posterior.

En todos los casos, las cargas aceptadas se sumarán al saldo remanente que registre la tarjeta y los rechazos generarán la devolución del dinero sobrante a la Entidad mediante transferencia bancaria.

**Importante:** Los archivos de cargas recibidos hasta las 17 horas de un día hábil serán impactados en las tarjetas en el proceso nocturno del día hábil siguiente.

---

### Página 9

### Descargas de tarjetas

Cuando la Entidad desee realizar descargas a las tarjetas, enviará a CABAL vía FTP un único archivo con el total de las mismas, de acuerdo al diseño denominado “Descarga de Tarjetas Precargadas”.

El archivo será sometido a una primera instancia de control de consistencia que consiste en verificar que la información contenida en el Trailer del archivo respecto a la cantidad de registros y a la sumatoria del importe coincida con el detalle informado.

Si el archivo es correcto, se procesará automáticamente. Si no, se notificará inmediatamente a la Entidad para su inmediato reemplazo.

Si como resultado de este proceso se generaran rechazos que impidieran la realización de las descargas solicitadas para algunas tarjetas en particular, éstas serán informadas en el archivo “Respuesta de Descarga de Tarjetas Precargadas” para que la Entidad, de considerarlo necesario, solicite una nueva descarga en un proceso posterior.

En todos los casos, las descargas aceptadas generarán la devolución del dinero resultante a la Entidad mediante transferencia bancaria correspondiente.

**Importante:** Los archivos de descargas recibidos hasta las 17 horas de un día hábil serán impactados en las tarjetas en el proceso nocturno correspondiente a dicho día hábil.

### Transferencia de saldos

Cuando la Entidad desee descargar ciertas tarjetas para cargar otras con ese dinero que se libera, estamos frente a un proceso de transferencia de saldos.

La Entidad deberá enviar a CABAL vía FTP un único archivo con el total de los movimientos de acuerdo al diseño denominado “Transferencia de Saldos de Tarjetas Precargadas”.

El archivo será sometido a una primera instancia de control de consistencia que consiste en verificar que la información contenida en el Trailer respecto a la cantidad de registros coincida con el detalle informado y que la sumatoria de los importes correspondientes a los códigos de carga coincida con la sumatoria de los importes de descarga. El importe total del archivo de transferencias siempre deberá ser “Cero”, para componer un efecto monetario nulo.

---

### Página 10

Si el archivo es correcto, se realizará un preproceso para verificar la aptitud de las tarjetas afectadas para recibir los movimientos solicitados. De estar todo en condiciones se procesará automáticamente. Si no, se notificará inmediatamente a la Entidad para su inmediato reemplazo.

El resultado de este proceso será informado en el archivo “Respuesta de Traslado de Saldos de Tarjetas Precargadas”.

### Descargas masivas

En cada fecha de carga masiva, la Entidad podrá solicitar a Cabal la descarga de saldos remanentes de todo el parque de tarjetas, mediante un código de operación especial informado en el Header del archivo de cargas. Dicho código (90) le indicará a Cabal que previamente deberá realizarse el proceso de descarga. Se considera saldo remanente al importe de cargas anteriores que el beneficiario no hubiera utilizado al momento de efectuar la carga.

Dicha descarga generará la devolución del dinero resultante a la Entidad mediante transferencia bancaria. La Entidad deberá informar a Cabal el número y CBU de la cuenta destinada para tal fin.

Cabal generará el archivo de descarga de manera mensual y únicamente para descargas masivas. Dicho proceso generará el archivo de “Respuesta de Descarga de Tarjetas Precargadas” mediante el cual la Entidad podrá verificar el importe de descarga correspondiente a cada cuenta.

Si el archivo de cargas fuera rechazado en su totalidad por error en el Header o Trailer, la descarga masiva no se aplicaría. La misma se generaría una vez que la Entidad deje el archivo con el error modificado e ingrese en el proceso normal.

---

## 5. Alta de tarjetas Precargadas – Emisiones individuales

La Entidad deberá transmitir a Cabal el archivo de “Altas de tarjetas Precargadas” antes de las 17 horas de días hábiles.

El mismo será procesado en el proceso nocturno.

---

### Página 11

## 6. Actualización de datos filiatorios de los beneficiarios de tarjetas precargadas

El Ente deberá remitir a CABAL las novedades de modificación de Apellido y Nombre, documento, fecha de nacimiento, estado civil o domicilio que correspondieran a través del archivo “Modificaciones y Bajas de Tarjetas Precargadas” que se describe en el Anexo 4.

## 7. Denuncia por extravío o hurto de tarjetas por parte de los beneficiarios

Los beneficiarios son los responsables de la custodia de sus plásticos y responsables de realizar la denuncia correspondiente al Centro de Atención Telefónica.

Para realizar la denuncia se deberán comunicar al teléfono suministrado por la Entidad, que estará disponible las 24 horas, los 365 días del año.

La llamada será derivada a un operador para que ubique y genere el bloqueo de la misma.

A partir del momento de la denuncia, la tarjeta denunciada queda inmediatamente bloqueada e inhabilitada para operar.

Por cuestiones de seguridad, no se podrá desbloquear una tarjeta, por lo que si el beneficiario luego la encontrara, la misma no podrá ser utilizada.

En el proceso diario del día hábil siguiente a la fecha de denuncia, se generará la reposición del plástico, al cual se le trasladará el saldo remanente que registraba la tarjeta denunciada.

La reposición del plástico podrá ser retirada por el beneficiario por el domicilio que la Entidad disponga, en el plazo que la misma determine. La entrega se realizará contra presentación de su Documento Nacional de Identidad, Libreta Cívica o Libreta de Enrolamiento.

## 8. Solicitudes de reemplazo de tarjetas por deterioro

Los usuarios se podrán comunicar a Cabal Responde las 24 horas, los 365 días del año, para solicitar el reemplazo de su tarjeta, en caso que se haya deteriorado.

La solicitud de reemplazo será tomada por IVR, solicitándole que ingrese su número de tarjeta y su número de documento. Si ambos datos son coincidentes con los registrados en los Archivos de Cabal, el trámite de reemplazo es terminado en esta instancia. Si existieran diferencias, luego de haberle dado al beneficiario la opción de reiterar el ingreso de los datos, la llamada será derivada a un operador humano para que ubique con mayor precisión la tarjeta que debe ser reemplazada.

En el proceso diario del día hábil siguiente a la fecha de solicitud, se generará la reposición del plástico, al cual se le trasladará el saldo remanente que registraba la tarjeta anterior.

---

### Página 12

A partir de la generación del nuevo plástico, el plástico anterior tendrá una vigencia de 180 días. En caso de que el usuario no utilice el nuevo plástico antes de que se cumpla dicho periodo, la tarjeta anterior será bloqueada.

La reposición del plástico podrá ser retirada por el beneficiario por el domicilio que la Entidad disponga, en el plazo que la misma determine. La entrega se realizará contra presentación de su Documento Nacional de Identidad, Libreta Cívica o Libreta de Enrolamiento.

## 9. Compras en los comercios adheridos

Los beneficiarios identificarán a los comercios adheridos a través del logo identificatorio del Plan. Solo podrán adherirse aquellos comercios que tengan domicilio en la Entidad y que pertenezcan a los rubros seleccionados por la misma.

El beneficiario deberá presentarse en los comercios con su tarjeta precargada y con su documento de identidad. Todas las compras las deberá realizar en forma presencial, dado que la tarjeta no está habilitada para débitos automáticos, pagos telefónicos, compras telefónicas y otras operatorias con tarjeta no presente.

Todas las compras deberán ser autorizadas en forma previa a su efectivización.

## 10. Consultas de beneficiarios sobre saldos y movimientos de compras

Los beneficiarios podrán llamar al Centro de Atención Telefónica las 24 horas, los 365 días del año al teléfono que la Entidad determine.

A través del IVR podrán consultar el saldo disponible de su tarjeta, la fecha y monto de la última carga y la información de las últimas compras realizadas.

En los POS que esté habilitada la opción, se imprimirá en el ticket de compra el saldo disponible de su tarjeta. Se destaca expresamente que dicha opción no está disponible en todos los POS instalados en el mercado, como así tampoco en los comercios que operan con sistemas propios.

El esquema del IVR se encuentra detallado en el Anexo 8.

## 11. Desconocimientos de los beneficiarios de compras en comercios

Cuando un beneficiario realice el desconocimiento de una compra aplicada a su saldo a través del Centro de Atención Telefónica, se le tomarán los datos del cupón que desconoce. Se le informará que la gestión del reclamo demandará aproximadamente 30 días.

CABAL procederá a solicitar al comercio la copia del ticket de venta suscripta por el usuario o bien a recuperar de su archivo el cupón original presentado por el comercio, como así también le solicitará a la Entidad copia del Legajo del beneficiario para proceder a la resolución del trámite.

---

### Página 13

CABAL ENVIARÁ MENSUALMENTE A LA ENTIDAD LOS DETALLES DE RESOLUCIÓN DE CADA DESCONOCIMIENTO.

Si el comercio no enviara la copia del ticket en el término de los 10 días posteriores a solicitado el mismo, la Entidad facultará a CABAL para que ingrese el contracargo al comercio, acreditando en ese mismo momento el monto del cupón a la tarjeta precargada.

Si el comercio envía a CABAL en el plazo establecido la copia del ticket o bien si CABAL lo hubiera recuperado de su archivo por tratarse de un cupón manual, CABAL realizará el dictamen correspondiente.

Pueden presentarse las siguientes alternativas:

- Si se considera que el cupón le corresponde al beneficiario, se finalizará el trámite y se dejará asentada dicha resolución, la cual será informada al beneficiario si éste se volviera a comunicar con el Centro de Atención Telefónica. Si el beneficiario insistiera con el reclamo, se le indicará que deberá concurrir al Centro de gestión correspondiente y presentar una nota detallando su reclamo.

- Si se considera que el cupón debe ser contracargado al comercio, se ingresará el movimiento que impactará como un débito al comercio y como un crédito al saldo de la tarjeta precargada. Si el beneficiario se comunicara con el Centro de Atención Telefónica, se le informará la resolución.

- Si la Entidad considera que se le debe realizar el crédito al beneficiario aún cuando el cupón no puede ser contracargado, deberá remitir a CABAL el monto del mismo en concepto de carga a través del archivo de Novedades de Carga. Además deberá informar esta situación a CABAL por mail, para que se pueda registrar la resolución final e informarla al beneficiario en caso que vuelva a comunicarse con el Centro de Atención Telefónica.

## 12. Ingreso y aplicación de contracargos o devoluciones de compras

*(El manual no desarrolla contenido específico en esta sección en las páginas 13-14)*

## 13. Solicitud de vinculación de comercios

Los pedidos de vinculación de comercios deberán ser realizados bajo el siguiente procedimiento:

1. Si el comercio está adherido a CABAL deberá suscribir el formulario de Adhesión al Programa, cuyo modelo se encuentra en el Anexo 15.

2. Si el comercio no estuviera adherido a Cabal, la Entidad deberá solicitar la adhesión del mismo al sector de Atención a Comercios de Cabal (comercios@cabal.com.ar).

En todos los casos, la Entidad deberá remitir a Cabal fotocopia del formulario de Adhesión al Programa. Deberá ser entregado al área de Producción por bolsa interna o presentarlos simultáneamente al retiro de los plásticos generados por reposición, de acuerdo a la operatoria definida previamente con la Entidad.

### Controles

Si no se informara el número de comercio en el formulario de Adhesión al Programa, Cabal verificará que el Número de CUIT, razón social y el domicilio informado por el comerciante sean coincidentes con los datos registrados en su base de comercios. En caso de existir diferencias, Cabal le informará dicha situación a la Entidad o al comerciante según corresponda, para su análisis.

Los comercios mayoristas quedan excluidos de la posibilidad de adhesión al Programa.

Si existiera más de un comercio habilitado bajo el mismo número de CUIT, en el formulario de Adhesión al Programa deberán informarse los números de comercio o los domicilios de los mismos de los que se solicite la vinculación.

---

### Página 15 – Generalidades

---

### Página 16

## 1. Crediware

El sistema de Crediware de consultas y actualizaciones permitirá a la entidad realizar consultas de cuentas y tarjetas, crear perfiles para usuarios y cambiar estados de tarjetas.

Para poder acceder a este sistema, la Entidad deberá solicitar al menos un usuario administrador que dará de alta los usuarios finales asignándoles los roles que crea necesarios. Una vez creados los usuarios, los mismos podrán acceder a los datos de las cuentas, tarjetas, movimientos, cargas, etc.

Asimismo, según el perfil otorgado a cada usuario, podrán cambiar los estados de los plásticos (habilitación o baja).

*Ver instructivos Crediware.*

## 2. Transmisión de archivos

Los archivos que la Entidad transmitirá a Cabal son los siguientes:

| Descripción del archivo | Desde la Entidad |
|------------------------|------------------|
| Alta de tarjetas | `ALTAUSUCBPRE.EEE` |
| Modificaciones y bajas | `NOVMOD.EEE` |
| Carga de saldo | `NOVCAR.EEE` |
| Descarga de saldo | `NOVDES.EEE` |
| Transferencia de saldo | `NOVTRAN.EEE` |

- `EEE`: Código de Entidad.
- Las altas posteriores a la emisión inicial podrán enviarse en un único archivo unificando altas, modificaciones y bajas (`NOVMOD.EEE`).

Archivos que transmitirá Cabal a la Entidad:

| Descripción del archivo | Hacia la Entidad |
|------------------------|------------------|
| Respuesta de altas | `RALT_EEE_aaaa-mm-dd_(SMPC).txt` |
| Respuesta de modificaciones | `RNOV_EEE_aaaa-mm-dd_(SMPC).txt` |
| Respuesta de cargas | `R*CARmmdd.EEE` |
| Respuesta de descargas | `R*DESmmdd.EEE` |
| Respuesta de transferencias | `R*TRANmmdd.EEE` |
| Archivo de rendición | `REND_aaaa-mm-dd_(SMPC).zip` |
| Maestro de Tarjetas | `PLAS_aaaa-mm-dd_(SMPC).txt` |
| Llamados recibidos | `EntEEE_aaaamm.txt` |

---

### Página 17

## 3. Validaciones – Archivo de Novedades de Cargas (`NOVCAR`)

Si el archivo posee en el tipo de Registro Detalle un Número de Cuenta en cero (posiciones 011 a 026), será rechazado en su totalidad dado que al no poseer el dato, no se tomará para el conteo de los registros totales ni para la sumatoria del importe total de cargas, lo que generará el rechazo del Trailer.

Asimismo, si el registro Header posee una fecha anterior a 30 días del día del proceso del archivo, el mismo será rechazado en su totalidad.

**Ejemplo de formato de archivo:**
```
TE04799000000000000000000003121209000063
TE0479900179900100012340006339000015000000000000000633900000001
TE047990017990000000000000063390000150000000000000063390000002
TE047990099999999999999990002000030000000000000000
```

## 4. Diagrama del IVR desarrollado para la atención telefónica de los beneficiarios

El servicio de IVR para los beneficiarios de las tarjetas precargadas consiste en el registro automático de solicitud de reemplazo de tarjetas por deterioro; servicio de información de disponibles, fecha e importe de última carga y últimas operaciones realizadas.

En el Anexo 8 se detalla el diagrama de los servicios mencionados.

## 5. Trámites atendidos en el Centro de Atención por operadores para los beneficiarios de las tarjetas precargadas

Ante el ingreso del llamado, el operador hará en primera instancia una rápida “Identificación Positiva” para asegurarse estar hablando con el beneficiario de la Tarjeta Precargada.

Para esto realizará una serie de preguntas que la persona deberá responder con real conocimiento de las respuestas:
- Nombre y Apellido
- Tipo y Nro. de Documento
- Fecha de Nacimiento

De acuerdo al tema que le plantee el beneficiario, el operador optará por registrar un trámite en nuestro sistema CRM, o informarle la dirección donde debe dirigirse para presentar su reclamo.

### Denuncia por extravío o hurto

- El operador procederá a identificar y bloquear el plástico de inmediato, dejando constancia de tal acción con el ingreso del trámite de “Denuncia por Extravío / Hurto de Tarjeta”.
- El trámite se cierra automáticamente.

---

### Página 18

### Reemplazo de tarjeta por deterioro (cuando no hayan coincidido los datos ingresados por IVR)

- El operador procederá a identificar y solicitar la reimpresión del plástico, mediante el ingreso del trámite “Reimpresión de Tarjeta por Deterioro”.
- Derivación automática al sector back-office desde donde se impactarán los datos para la emisión de la reposición del plástico.
- El trámite se cierra una vez ingresadas las novedades correspondientes en el sistema.
- **Tiempo estimado de cierre:** 2 días hábiles.

### Desconocimiento de Compra y/o Saldo de Tarjeta

- Se toma este reclamo cuando el beneficiario dice desconocer una compra o una autorización registrada en nuestro sistema, que fueran aplicadas al saldo de su tarjeta.
- Derivación automática al sector back-office desde donde se gestionará el recupero del cupón y la verificación de las firmas.
- Se gestionarán las acciones necesarias para concluir sobre la veracidad de la compra, y a partir de allí se podrá decidir con una resolución favorable al beneficiario con un crédito al saldo de la tarjeta precargada y un débito al comercio, o por el contrario si le corresponde al beneficiario se cerrará el trámite sin generar movimientos monetarios.
- El trámite se cierra una vez tomada esta decisión e ingresados los movimientos si correspondieran.
- **Tiempo estimado de cierre:** 30 días.

### Consultas generales

- Se dejará constancia del llamado del beneficiario, registrando en una plantilla de detalle o comentario el tema de la consulta.
- El trámite se cierra automáticamente.

Si los beneficiarios consultaran sobre los siguientes temas, se les indicará que para realizar la gestión deben comunicarse al teléfono que defina la Entidad:

- Modificación de Apellido y Nombre, número de documento, fecha de nacimiento, estado civil, etc.
- Modificación de domicilio.
- Solicitud del detalle de los comercios adheridos al Programa Tarjeta Cabal Precargada.
- Reclamo por el monto de la carga, falta de la misma o la fecha de la próxima carga.

---



# RULES.md — Especificaciones Técnicas y Reglas de Desarrollo Cabal (Anexos 1-13)

Este archivo de configuración técnica define las estructuras de datos estrictas, especificaciones de longitud de registro fija, matrices de validación bit a bit y catálogos de constantes extraídos directamente del manual de referencia oficial (Páginas 20 a 44). Diseñado para máxima eficiencia de tokens en agentes de IA y sistemas automatizados de parsing.

---

## 📌 Directivas Generales de Desarrollo e Intercambio (Host Cabal)
1. **Longitudes Fijas Obligatorias:** Todas las tramas enviadas vía FTP deben respetar milimétricamente las posiciones de bytes indicadas. El incumplimiento de la longitud total provocará el rechazo automático del lote en el proceso nocturno centralizado.
2. **Políticas de Relleno (Padding):** - Campos Numéricos: Rellenar obligatoriamente con **ceros a la izquierda** (`N-0`).
   - Campos Alfanuméricos: Rellenar obligatoriamente con **espacios a la derecha** (`X`).
3. **Mapeo de Caracteres Especiales:** El alfabeto nativo de la interfaz no soporta el carácter **Ñ**. Todo desarrollo frontend o backend debe sanitizar los strings y **reemplazar obligatoriamente la Ñ por el carácter #** antes de la transmisión.

---

## 📑 ANEXO 1 — Novedades de Altas de Tarjetas Precargadas (ALTAUSUCBPRE)
* **Nombre de Archivo en la Entidad:** `ALTAUSUCBPRE.EEE` (Donde `EEE` es el código de entidad).
* **Constantes operativas de alta:** Código de Entidad Emisora `782` y Sucursal `001`; el campo combinado Entidad / Sucursal se transmite como `782001`.
* **Longitud de Registro:** 128 BYTES (Formato Fijo).
* **Estructura Requerida:** Todo beneficiario requiere obligatoriamente **3 registros consecutivos** en el archivo de texto, vinculados entre sí mediante el campo coincidente `Número de Formulario`.

### Layout Estricto: Registro Nro. 1 (Datos Filiatorios Básicos)
| Desde | Hasta | Campo Técnico | Long. | Tipo | Regla de Validación / Formato |
| :---: | :---: | :--- | :---: | :---: | :--- |
| `001` | `004` | Constante Registro | 4 | X | Debe ser estrictamente `"TE01"` |
| `005` | `010` | Prefijo Entidad | 6 | N-0 | Código numérico asignado a la entidad bancaria |
| `011` | `012` | Código Producto | 2 | N-0 | Valor constante invariable: `"60"` |
| `013` | `020` | Campo Reservado | 8 | N-0 | Completar obligatoriamente con ceros |
| `021` | `022` | Código Operación | 2 | N-0 | Valor constante invariable: `"01"` |
| `023` | `028` | Entidad / Sucursal | 6 | N-0 | Estructura interna: `EEESSS` |
| `029` | `053` | Apellido y Nombre | 25 | X | Apellido (Máx 16) seguido obligatoriamente por `"/"` y el Nombre |
| `054` | `078` | Domicilio (Calle) | 25 | X | Texto plano de la arteria vial |
| `079` | `083` | Domicilio (Número) | 5 | N | Altura catastral |
| `084` | `085` | Domicilio (Piso) | 2 | X | Número de piso |
| `086` | `087` | Domicilio (Depto) | 2 | X | Identificador de departamento/oficina |
| `088` | `117` | Localidad | 30 | X | Nombre de la localidad de residencia |
| `118` | `119` | Provincia | 2 | N | Mapear obligatoriamente según tabla del **Anexo 9** |
| `120` | `122` | Código de País | 3 | N | Completar obligatoriamente con ceros |
| `123` | `123` | Sexo | 1 | X | Valores lógicos admitidos: `"F"` (Femenino) o `"M"` (Masculino) |
| `124` | `128` | Nro. de Formulario | 5 | N-0 | Secuencial único de vinculación de los 3 registros |

### Layout Estricto: Registro Nro. 2 (Datos de Identificación Civil)
| Desde | Hasta | Campo Técnico | Long. | Tipo | Regla de Validación / Formato |
| :---: | :---: | :--- | :---: | :---: | :--- |
| `001` | `004` | Constante Registro | 4 | X | Debe ser estrictamente `"TE01"` |
| `005` | `010` | Prefijo Entidad | 6 | N-0 | Debe coincidir con el Registro 1 |
| `011` | `012` | Código Producto | 2 | N-0 | Valor constante invariable: `"60"` |
| `013` | `020` | Campo Reservado | 8 | N-0 | Completar obligatoriamente con ceros |
| `021` | `022` | Código Operación | 2 | N-0 | Valor constante invariable: `"02"` |
| `023` | `030` | Código Postal | 8 | X | Formato estricto de la República Argentina: `BNNNNBBB` |
| `031` | `035` | Teléfono (Prefijo) | 5 | N-0 | Código de área de telediscación interurbana |
| `036` | `043` | Teléfono (Número) | 8 | N-0 | Número telefónico directo de contacto |
| `044` | `044` | Estado Civil | 1 | X | Valores lógicos: `"C"` (Casado), `"S"` (Soltero), `"V"` (Viudo), `"O"` (Otro) |
| `045` | `052` | Fecha Nacimiento | 8 | N-0 | Formato estructurado rígido: `DDMMAAAA` |
| `053` | `054` | Tipo Documento 1 | 2 | N-0 | Mapear obligatoriamente según tabla del **Anexo 10** |
| `055` | `065` | Nro. Documento 1 | 11 | N-0 | Número de la cédula o DNI |
| `066` | `067` | Provincia del Doc. | 2 | N-0 | Si Tipo es Cédula (`4`), usar Anexo 9. Si es otro documento, usar ceros |
| `068` | `078` | CUIT / CUIL | 11 | N-0 | Clave Única de Identificación Tributaria/Laboral |
| `079` | `091` | Filler | 13 | N-0 | Espacio técnico de alineación. Rellenar con ceros |
| `092` | `098` | Grupo de Afinidad | 7 | N-0 | Parámetro comercial del plan social. Completar con ceros |
| `099` | `123` | Mensaje Especial | 25 | X | Texto opcional. Se graba e imprime en la cuarta línea física del plástico |
| `124` | `128` | Nro. de Formulario | 5 | N-0 | Debe ser idéntico al del Registro 1 |

### Layout Estricto: Registro Nro. 3 (Parámetros Técnicos de Habilitación)
| Desde | Hasta | Campo Técnico | Long. | Tipo | Regla de Validación / Formato |
| :---: | :---: | :--- | :---: | :---: | :--- |
| `001` | `004` | Constante Registro | 4 | X | Debe ser estrictamente `"TE01"` |
| `005` | `010` | Prefijo Entidad | 6 | N-0 | Debe coincidir con los registros anteriores |
| `011` | `012` | Código Producto | 2 | N-0 | Valor constante invariable: `"60"` |
| `013` | `020` | Campo Reservado | 8 | N-0 | Completar obligatoriamente con ceros |
| `021` | `022` | Código Operación | 2 | N-0 | Valor constante invariable: `"03"` |
| `023` | `024` | Código Profesión | 2 | N-0 | Completar fijamente con el valor `"99"` |
| `025` | `025` | Tipo de Producto | 1 | X | Completar fijamente con el carácter `"P"` |
| `026` | `026` | Distribución PIN | 1 | X | Completar fijamente con `"S"` (Entrega controlada en Filial) |
| `027` | `027` | Distribución Tarjeta | 1 | X | Completar fijamente con `"S"` (Entrega controlada en Filial) |
| `028` | `028` | Señal Vinculación | 1 | X | Completar obligatoriamente con un espacio en blanco (`" "`) |
| `029` | `113` | Reserva de Sistema | 85 | X | Completar estrictamente con espacios vacíos |
| `114` | `114` | Clase de Cuenta | 1 | X | Completar fijamente con la constante `"A"` |
| `115` | `115` | Tipo de PIN | 1 | X | Completar fijamente con `"1"` (Indica generación de PIN Telefónico) |
| `116` | `120` | Reserva Secundaria | 5 | X | Completar estrictamente con espacios vacíos |
| `121` | `123` | Origen Novedad | 3 | X | Código identificador del sistema: `"TPC"` |
| `124` | `128` | Nro. de Formulario | 5 | N-0 | Debe ser idéntico al del Registro 1 y 2 |

---

# Anexo 2 - Respuesta de Altas de Tarjetas Precargadas

**Nombre:** 782_out_altas_tpc  
**Longitud:** 196 BYTES

---

## Registro Nro. 1 (1001004 "TE01" 4X)

| Desde | Hasta | Descripción | Bytes | Tipo |
|-------|-------|-------------|-------|------|
| 001 | 004 | "TE01" | 4 | X |
| 005 | 020 | Número de tarjeta asignado<br/>Compuesto por:<br/>005 a 010 → Bin<br/>011 a 012 → Código de producto: 60<br/>013 a 020 → Últimos 8 dígitos de la tarjeta | 16 | N |
| 021 | 022 | Código de Operación (Fijo 01) | 2 | N |
| 023 | 028 | Entidad y sucursal emisora (Formato EEESSS) | 6 | N |
| 029 | 053 | Apellido y nombre del titular.<br/>• Apellido hasta 16<br/>• Carácter “/”<br/>• Nombre<br/>El apellido puede ser menor a 16 posiciones | 25 | X |
| 054 | 087 | Domicilio (Calle, Número, Piso, Departamento) | 25 (Calle 5, Nro 2, Piso 2, Depto 2) | X |
| 088 | 117 | Localidad | 30 | X |
| 118 | 119 | Provincia (Según Anexo 9) | 2 | N |
| 120 | 122 | País (Siempre ceros) | 3 | N |
| 123 | 123 | Sexo (F: Femenino, M: Masculino) | 1 | X |
| 124 | 128 | Número de Formulario | 5 | N |
| 129 | 129 | Código de ingreso<br/>0: Aceptada<br/>1: Rechazada | 1 | N |
| 130 | 165 | Código de rechazo (seguido por un espacio)<br/>Se pueden informar hasta 9 códigos de error. | 36 | X |
| 166 | 168 | Filler | 3 | X |
| 169 | 174 | Entidad y sucursal emisora (Formato EEESSS) | 6 | N |
| 175 | 180 | Fecha de proceso (Formato AAMMDD) | 6 | N |
| 181 | 196 | Número de cuenta | 16 | N |

---

## Registro Nro. 2 (1001004 "TE01" 4X)

| Desde | Hasta | Descripción | Bytes | Tipo |
|-------|-------|-------------|-------|------|
| 001 | 004 | "TE01" | 4 | X |
| 005 | 020 | Número de tarjeta asignado<br/>Compuesto por:<br/>005 a 010 → Bin<br/>011 a 012 → Código de producto: 60<br/>013 a 020 → Últimos 8 dígitos de la tarjeta | 16 | N |
| 021 | 022 | Código de Operación (Fijo 02) | 2 | N |
| 023 | 030 | Código Postal (Formato BNNNNBBB) | 8 | X |
| 031 | 043 | Teléfono<br/>Telediscado<br/>Nro. de teléfono | 5 (8N, 0N, 0N) | N |
| 044 | 044 | Estado Civil<br/>C: Casado, S: Soltero, V: Viudo, O: Otro | 1 | X |
| 045 | 052 | Fecha de Nacimiento (DDMMAAAA) | 8 | N |
| 053 | 054 | Tipo de documento del Documento 1 (según anexo 10) | 2 | N |
| 055 | 065 | Número de documento del Documento 1 | 11 | N |
| 066 | 067 | Provincia del Documento 1.<br/>Si el tipo es Cédula, cumplimentar según tabla de provincias, si es otro documento cumplimentar ceros. | 2 | N |
| 068 | 078 | Número de CUIT o CUIL | 11 | N |
| 079 | 091 | Filler | 13 | N |
| 092 | 098 | Grupo de afinidad (Cumplimentar con ceros) | 7 | N |
| 099 | 123 | Mensaje especial para la Tarjeta<br/>Si viene informado se graba en la 4ta. línea del plástico. | 25 | X |
| 124 | 128 | Número de Formulario | 5 | N |
| 129 | 129 | Código de ingreso<br/>0: Aceptada<br/>1: Rechazada | 1 | N |
| 130 | 165 | Código de rechazo (seguido por un espacio)<br/>Se pueden informar hasta 9 códigos de error. | 36 | X |
| 166 | 168 | Filler | 3 | X |
| 169 | 174 | Entidad y sucursal emisora (Formato EEESSS) | 6 | N |
| 175 | 180 | Fecha de proceso (Formato AAMMDD) | 6 | N |
| 181 | 196 | Número de cuenta | 16 | N |

---

## Registro Nro. 3 (1001004 "TE01" 4X)

| Desde | Hasta | Descripción | Bytes | Tipo |
|-------|-------|-------------|-------|------|
| 001 | 004 | "TE01" | 4 | X |
| 005 | 020 | Número de tarjeta asignado<br/>Compuesto por:<br/>005 a 010 → Bin<br/>011 a 012 → Código de producto: 60<br/>013 a 020 → Últimos 8 dígitos de la tarjeta | 16 | N |
| 021 | 022 | Código de Operación (Fijo 03) | 2 | N |
| 023 | 024 | Código de profesión (Cumplimentar con '99') | 2 | N |
| 025 | 025 | Tipo de producto (Cumplimentar con 'P') | 1 | X |
| 026 | 026 | Señal de entrega en filial de sobre PIN (Siempre 'S')<br/>"=" → Distribución Normal<br/>"S" → Entrega en Filial | 1 | X |
| 027 | 027 | Señal de entrega en filial de la tarjeta (Siempre 'S')<br/>"=" → Distribución Normal<br/>"S" → Entrega en Filial | 1 | X |
| 028 | 028 | Señal de Vinculación en el alta (Siempre espacio)<br/>"=" → No tiene Vinculación<br/>"S" → Tiene Vinculación | 1 | X |
| 029 | 113 | Reserva (Siempre espacios) | 85 | X |
| 114 | 114 | Clase (Siempre "A") | 1 | X |
| 115 | 115 | Tipo de PIN para Tarjeta Precargada (Siempre "1")<br/>"0" → PIN en papel carta<br/>"1" → PIN Telefónico | 1 | X |
| 116 | 120 | Reserva (Siempre espacios) | 5 | X |
| 121 | 123 | Origen de la Novedad (Siempre 'TPC') | 3 | X |
| 124 | 128 | Número de Formulario | 5 | N |
| 129 | 129 | Código de ingreso<br/>0: Aceptada<br/>1: Rechazada | 1 | N |
| 130 | 165 | Código de rechazo (es seguido por un espacio)<br/>Se pueden informar hasta 9 códigos de error.<br/>Ver Anexo 12 | 36 | X |
| 166 | 168 | Filler | 3 | X |
| 169 | 174 | Entidad y sucursal emisora (Formato EEESSS) | 6 | N |
| 175 | 180 | Fecha de proceso (Formato AAMMDD) | 6 | N |
| 181 | 196 | Número de cuenta | 16 | N |

---

**Leyenda de tipos de datos:**

- **X**: Alfanumérico  
- **N**: Numérico  
- **B**: Binario  
- **Z**: Zoneado  
- **P**: Empaquetado
## 📑 ANEXO 3 — Archivos de Cargas, Descargas y Rendiciones (NOVCAR / NOVDES)
* **Longitud Fija Obligatoria:** 80 BYTES (Aplica estrictamente a Header, Detalle y Trailer).
* **💥 REGLA CRÍTICA DE VALIDACIÓN (RECHAZO EN BLOQUE):** Si en un registro de tipo **Detalle** el campo *Número de Cuenta* (posiciones 011 a 026) es igual a **cero**, el motor de parsing ignorará la línea para el conteo global y el control de sumatorias del Trailer. Esto romperá la consistencia del lote provocando el **rechazo fulminante de la totalidad del archivo**.

### Layout unificado de Estructura de Cargas (NOVCAR)
```
[HEADER - REGISTRO DE CABECERA]
001-004 (4X)   -> Identificador de lote de datos: "TE04"
005-007 (3N-0) -> Código de Entidad Emisora (EEE)
008-010 (3N-0) -> Sucursal de control de origen. Forzar siempre: "000"
011-026 (16N-0)-> Registro de seguridad de cabecera. Forzar siempre a cero.
027-032 (6N-0) -> Fecha exacta de empaquetado del lote (Formato: DDMMAA)
033-038 (6N-0) -> Hora exacta de empaquetado del lote (Formato: HHMMSS)
039-080 (42X)  -> Filler técnico de cabecera. Completar con espacios.

[DETALLE - REGISTRO DE MOVIMIENTO INDIVIDUAL]
001-004 (4X)   -> Identificador de registro de movimiento: "TE04"
005-010 (6N-0) -> Código consolidado de la Entidad emisora (EEESSS). Forzar siempre: `782001`
011-026 (16N-0)-> NÚMERO DE CUENTA CABAL DEL BENEFICIARIO (PROHIBIDO VALOR CERO)
027-028 (2N-0) -> Código de Operación estándar de abono: "63" (Indica Crédito Directo)
029-030 (2N-0) -> Subcódigo de Operación para cuentas precargadas: Completar con "39"
031-039 (9N-2) -> Importe monetario neto destinado exclusivamente a compras ordinarias
040-048 (9N-2) -> Importe monetario neto destinado exclusivamente a extracciones en cajeros
049-054 (6N)   -> Motivo de carga específico parametrizado. Forzar siempre: `006239`
055-062 (8N)   -> Número de carga incremental por registro Detalle, desde `00000001`
063-080 (18X)  -> Filler técnico de línea. Completar con espacios.

[TRAILER - REGISTRO DE CIERRE Y CONTROL DE CONCORDANCIA]
001-004 (4X)   -> Identificador de registro de cierre: "TE04"
005-007 (3N-0) -> Código de Entidad Emisora (EEE)
008-010 (3N-0) -> Sucursal de control de origen: "000"
011-026 (16N-0)-> Identificador de cierre lógico: Completar enteramente con NUEVES (9)
027-031 (5N-0) -> CANTIDAD EXACTA DE REGISTROS DE DETALLE TRANSMITIDOS EN EL ARCHIVO
032-041 (10N-2)-> SUMATORIA ARITMÉTICA EXACTA DE TODOS LOS IMPORTES DE COMPRAS ENVIADOS
042-051 (10N-2)-> SUMATORIA ARITMÉTICA EXACTA DE TODOS LOS IMPORTES DE CAJEROS ENVIADOS
052-080 (29X)  -> Filler técnico de cierre. Completar con espacios.
```

### Reglas Específicas para Descargas Individuales (NOVDES)
El archivo `NOVDES` copia exactamente el layout de 80 bytes de cargas, modificando los siguientes puntos de lógica:
1. **Header (Posiciones 039-040):** Inyectar obligatoriamente el código de descarga técnica `"62"`.
2. **Detalle (Posiciones 027-028):** Código de Operación de quita de fondos: `"62"` (Indica Débito Directo).
3. **Control de Solvencia:** El Host denegará la descarga inyectando el error `009` si el saldo disponible en la cuenta es inferior al débito solicitado.

---

## 📑 ANEXO 4 — Archivo de Modificaciones y Bajas de Tarjetas (NOVMOD)
* **Longitud de Registro:** 128 BYTES (Formato Fijo).

### Estructura Rígida de Segmentos Específicos:
Todos los registros inician con el mismo bloque base de identificación: `Constante "TE01" (001-004) + Prefijo (005-010) + Tipo Tarjeta "60" (011-012) + Nro Tarjeta (013-020) + Código Operación Fijo "10" (021-022)`. El comportamiento del parser varía según el **Código de Modificación** ubicado en las posiciones **023-024**:

* **`01` (Grupo de Afinidad):** Modifica el comportamiento comercial. Campos obligatorios: `Número de Grupo de Afinidad (025-031, 7N-0) + Señal de Retención (032, 1X) + Señal de Reposición (033, 1X) + Domicilio de Envío (034-092)`.
* **`02` (Modificación de Nombre):** Actualiza el string de impresión del plástico. Campos obligatorios: `Nombre Nuevo Completo (025-049, 25N-0) + Señal de Retención de Plástico (050, 1X) + Domicilio de Envío Completo (051-109)`.
* **`04` (Cambio de Domicilio):** Reestructura la dirección postal. Campos obligatorios: `Calle y Altura (025-058, 34X) + Localidad (059-088, 30X) + Código Postal (089-096, 8N-0) + Provincia de Entrega (097-098, 2N-0) + País (099-101, Ceros)`.
* **`06` (Teléfono):** Cambia datos de seguridad rápida. Campos obligatorios: `Prefijo (025-029, 5N-0) + Número Telefónico Directo (030-037, 8N-0)`.
* **`07` (Fecha de Nacimiento):** Inyecta correcciones biográficas. Campos obligatorios: `Fecha Nueva (025-032, 8N-0 en formato DDMMAAAA)`.
* **`08` (Sexo):** Modifica marca de cuenta. Campos obligatorios: `Sexo (025, 1X. Valores admitidos: "F" o "M")`.
* **`09` (Estado Civil):** Actualiza maestros. Campos obligatorios: `Código Estado Civil (025, 1X. Valores lógicos: "C", "S", "D", "V", "O")`.
* **`10` (Profesión):** Modifica scoring. Campos obligatorios: `Código de Actividad Homologada (025-026, 2N-0)`.
* **`12` (Documento de Identidad):** Actualiza el registro de validación civil. Campos obligatorios: `Tipo Documento (025-026, 2N-0) + Número Documento (027-037, 11N-0) + Provincia de Emisión (038-039, 2N-0)`.
* **`14` (CUIT / CUIL):** Inyección tributaria obligatoria. Campos obligatorios: `Número de CUIT/CUIL Neto (025-035, 11N-0)`.
* **`15` (Mensaje en Tarjeta):** Modifica embozado alternativo. Campos obligatorios: `Nuevo Mensaje Grabar (025-049, 25N-0)`.
* **`17` (Bloqueo / Anulación):** Ejecuta restricciones inmediatas. Campos obligatorios: `Causa Formal del Bloqueo (025-026, 2N-0. Código 02 = Decisión de la entidad / Código 03 = Decisión del usuario)`.

### ⚡ Regla Excepcional de Habilitación por Lote (Código "1020")
Para ejecutar el alta en masa de tarjetas que han sido entregadas físicamente a los beneficiarios se utiliza un registro plano de optimización de tokens: `Constante "TE01" (001-004) + Número de Tarjeta de 16 dígitos (005-020) + CÓDIGO FIJO OPERATIVO "1020" (021-024) + Filler completado enteramente con espacios vacíos (025-128)`.

---

## 📑 ANEXO 5 — Registro de Transferencia de Saldos (NOVTRAN)
* **Nombre de Archivo de Entrada:** `NOVTRAN.EEE` | **Nombre de Archivo de Respuesta:** `R*TRANmmdd.EEE`
* **Longitud de Registro:** 80 BYTES (Estructura de longitud fija).

### Reglas de Control Algorítmico Rígidas:
1. **Subcódigo Operativo Obligatorio:** En todos los registros de tipo Detalle, el campo Subcódigo de Operación (posiciones 029-030) debe completarse estrictamente con la constante `"80"`.
2. **Código de Cabecera:** El Header debe contener el identificador técnico de transferencias de saldo `"80"` en la posición 039-040.
3. **Consistencia de Suma Cero:** El archivo debe estructurarse obligatoriamente de forma simétrica. Por cada registro de quita de fondos (Código Op `"62"` - Débito) de una tarjeta origen, debe existir su contraparte exacta de abono (Código Op `"63"` - Crédito) hacia una tarjeta destino. El sumador consolidado del campo *Importe para Compras* calculado en el Trailer del archivo **debe ser exactamente igual a Cero**. Cualquier desviación decimal rechazará la totalidad de las transferencias.

---

## 📑 ANEXO 6 AL 10 — Tablas Maestras de Configuración del Negocio

### Anexo 6: Archivo de Rendición Mensual consolidado
* **Nombre de Archivo:** `rendicionmmaa.EEE` (Longitud fija de 130 bytes).
* **💥 REGLA TÉCNICA DE CONTRACARGOS:** Ante una devolución o contracargo de comercio, el registro se grabará indexando de forma mandatoria la **fecha original en la que se efectuó la compra base**, sin importar el mes de liquidación de la devolución.

### Anexo 7: Maestro Semanal de Estado del Parque (MAEPLAS)
* **Nombre de Archivo:** `MAEPLAS` (Longitud fija de 110 bytes, periodicidad semanal).
* **Regla de Control de Emisión:** La posición física número **15** del campo *Número de Tarjeta* almacena un dígito numérico que representa de forma inequívoca la **versión del plástico** actual.

### Anexo 9: Catálogo Rígido de Códigos de Provincias
| Código | Jurisdicción Oficial | Código | Jurisdicción Oficial |
| :---: | :--- | :---: | :--- |
| `01` | Capital Federal | `12` | Provincia de La Rioja |
| `02` | Provincia de Buenos Aires | `13` | Provincia de Mendoza |
| `03` | Provincia de Catamarca | `14` | Provincia de Misiones |
| `04` | Provincia de Córdoba | `15` | Provincia de Neuquén |
| `05` | Provincia de Corrientes | `16` | Provincia de Río Negro |
| `06` | Provincia de Chaco | `17` | Provincia de Salta |
| `07` | Provincia de Chubut | `18` | Provincia de San Juan |
| `08` | Provincia de Entre Ríos | `19` | Provincia de San Luis |
| `09` | Provincia de Formosa | `20` | Provincia de Santa Cruz |
| `10` | Provincia de Jujuy | `21` | Provincia de Santa Fe |
| `11` | Provincia de La Pampa | `22` | Provincia de Santiago del Estero |
| `23` | Provincia de Tucumán | `41` | Provincia de Tierra del Fuego |

### Anexo 10: Matriz de Tipos de Documentos Civiles
Mapeo entero requerido para los campos de validación de identidad en tramas de Altas, Modificaciones y Maestros:
* **`1`**: LE (Libreta de Enrolamiento)
* **`2`**: LC (Libreta Cívica)
* **`3`**: DNI (Documento Nacional de Identidad)
* **`4`**: CI (Cédula de Identidad)
* **`5`**: DNE (Documento para Extranjeros)
* **`6`**: DIN (Carnet o Documento Internacional)
* **`7`**: CIM (Certificado Provisorio de Personas Nacidas en Islas Malvinas)
* **`8`**: PER (Permiso de Ingreso a Extranjeros)
* **`9`**: Otros (Pasaportes visados, credenciales extranjeras o certificados habilitados)

---

## 📑 ANEXOS 11 AL 13 — Matrices de Control de Errores y Validaciones de Estado

### Anexo 11: Diccionario de Errores en Operaciones de Carga y Descarga (RCAR / RDES)
| Segmento | Código | Mensaje Técnico del Sistema | Causa Root / Acción de Solución |
| :---: | :---: | :--- | :--- |
| **HEADER** | `001` | Entidad Inexistente o Falta Trailer | Falta el bloque de cierre técnico al final del archivo o el código de banco está fuera de los maestros. |
| **HEADER** | `002` | Nro. de Tarjeta diferente de ceros | Error de formato en cabecera: las posiciones de cuenta deben ser estrictamente `0`. |
| **HEADER** | `003` | Fecha de proceso inválida | Formato de fecha no numérico o fuera del calendario real. |
| **HEADER** | `004` | Hora de proceso no numérica | Formato de hora mal estructurado en la cabecera. |
| **HEADER** | `005` | Código de operación inválido | El código del Header no coincide con ninguna operación válida (`80`, `62`, `63`, `90`). |
| **HEADER** | `006` | Rechazo por tesorería | El departamento de control financiero bloqueó manualmente la aplicación del lote. |
| **DETALLE** | `001` | Falta Header y Trailer | El archivo de texto carece de las estructuras de control iniciales o de cierre. |
| **DETALLE** | `002` | Tarjeta no numérica o inexistente | El número de cuenta ingresado en la línea no existe en la base histórica de Cabal. |
| **DETALLE** | `003` | Código de operación diferente de 62/63 | La línea posee una operación prohibida para este canal. |
| **DETALLE** | `004` | Importe de Compra erróneo | El formato del número decimal de compras posee caracteres alfabéticos o longitud inválida. |
| **DETALLE** | `005` | Importe de Retiros erróneo | El formato del número decimal de extracción en cajeros es inválido. |
| **DETALLE** | `006` | Desalineación de Operación | El código de operación de la línea individual difiere del tipo de archivo declarado en el Header. |
| **DETALLE** | `007` | Subcódigo de operación erróneo | No se utilizó la constante `80` para transferencias o `39` para cargas/descargas ordinarias. |
| **DETALLE** | `008` | Inconsistencia de Estado (Bloqueo) | La tarjeta se encuentra en un estado inactivo o bloqueado que impide procesar la carga o descarga. |
| **DETALLE** | `009` | **Importe mayor al disponible** | Rechazo por saldo insuficiente: la cuenta no posee fondos líquidos para cubrir la descarga o transferencia. |
| **DETALLE** | `010` | Motivo de carga mal informado | Estructura incorrecta en los parámetros de justificación de fondos. |
| **DETALLE** | `011` | Número de carga mal informado | El secuencial provisto por la entidad está duplicado o mal estructurado. |
| **DETALLE** | `012` | Consulte a Cabal | Error genérico crítico de base de datos en el Host central. |
| **DETALLE** | `017` | Entidades diferentes | Conflicto de clearing: el código de banco de la línea no coincide con los registros de control. |
| **TRAILER** | `002` | Nro. Tarjeta diferente de nueves | Error de formato: el campo de control de cierre debe contener estrictamente el valor `9999999999999999`. |
| **TRAILER** | `003` | Descalce en cantidad de registros | El contador físico de líneas del proceso arrojó un número distinto al declarado en el Trailer. |
| **TRAILER** | `004` | Descalce en sumatoria de Compras | La suma aritmética de los campos de importe difiere del totalizador de control enviado. |
| **TRAILER** | `005` | Descalce en sumatoria de Cajeros | La suma aritmética de los campos de cajeros difiere del totalizador de control enviado. |
| **TRAILER** | `006` | Importe carga <> descarga | Rompe la regla de **Suma Cero** en el archivo consolidado de transferencias inter-tarjetas. |
| **TRAILER** | `008` | Carga Duplicada | El identificador único del lote ya fue inyectado y procesado en el sistema central anteriormente. |
| **TRAILER** | `009` | Error en descarga previa | Falló el proceso algorítmico automatizado de limpieza de saldos remanentes (Código `90`). |

### Anexo 12: Diccionario de Errores para Altas y Modificaciones Filiactorias (RALT)
| Código | Descripción Oficial del Rechazo en Base de Datos | Código | Descripción Oficial del Rechazo en Base de Datos |
| :---: | :--- | :---: | :--- |
| `001` | Identificación no válida | `033` | Grupo afinidad no válido |
| `002` | Código de novedad inexistente | `034` | No existe grupo de afinidad en los maestros |
| `003` | Novedad repetida en proceso en el mismo lote | `036` | Número de CUIT/CUIL no numérico o inválido |
| `004` | Estructura de domicilio errónea o fuera de formato | `036` | No se puede habilitar adicional: titular inactivo |
| `005` | Falta informar campo obligatorio: Localidad | `037` | Grupo afinidad dado de baja o no habilitado |
| `006` | Código de provincia no válido (Revisar Anexo 9) | `041` | Señal de retención mal formada en la trama |
| `007` | Código Postal no numérico o en blanco | `060` | Campo Nombre obligatorio en blanco |
| `008` | Código de país no numérico | `061` | Señal de reemisión no válida |
| `009` | Teléfono no numérico | `065` | Error intrínseco en campo Documento 1 |
| `010` | Código de sexo erróneo o fuera de parámetro | `066` | Error en Tipo de Documento 1 |
| `011` | Estado Civil erróneo o código no reconocido | `080` | Código de modificación no válido en la posición 23 |
| `012` | Fecha de nacimiento ilógica o inconsistente | `081` | Modificación para tarjeta inhabilitada o dada de baja |
| `013` | Titular con menor edad de la permitida por el plan | `088` | Banco emisor nuevo distinto del anterior registrado |
| `014` | Profesión inválida o no nomenclada | `092` | Reactivación permitida exclusivamente para causa 08 |
| `021` | Bloqueo causa 05 para tarjeta con causa 07 | `115` | Mensaje de cuarta línea mal formado (Excede longitud) |
| `027` | Causa tarjeta no existe en la parametrización | `301` | Banco/Sucursal inexistente en clearing central |
| `028` | Solo las siguientes causas están habilitadas: 02,03,14| `305` | Nro de cuenta no numérico o mal dígito verificador |
| `031` | Número de Formulario inválido o corrupto | `306` | Tarjeta inexistente en los maestros históricos |
| `032` | La edad del titular supera la máxima definida | `999` | **Error Interno al procesar: Comuníquese con Sistemas**|

### Anexo 13: Matriz de Estados y Causas de Bloqueo de Tarjetas
Definición de las constantes numéricas que gobiernan el ciclo de vida de los plásticos en la red de terminales y cajeros automáticos:

| Código | Denominación | Regla Técnica de Operación / Comportamiento del Sistema |
| :---: | :--- | :--- |
| `01` | Denuncia por extravío | Estado irreversible de seguridad. Bloquea de forma inmediata el clearing del plástico. |
| `02` | Anulación por Entidad | Estado administrativo definitivo. Ordenado por el ente gestor para dar de baja el beneficio. |
| `03` | Anulación | Baja general del registro de la cuenta en los sistemas centrales. |
| `04` | Denuncia por hurto | Bloqueo irreversible de seguridad ante robo. Deniega compras automáticas. |
| `07` | Reemplazo | Estado intermedio. El plástico físico se cancela y se ordena la impresión de una nueva versión. |
| `08` | No habilitada | Estado nativo inicial (Preemisión). La tarjeta está impresa pero bloqueada para su uso. |
| `14` | Bloqueo Transitorio | **Restricción Exclusiva de Transición:** Suprime la posibilidad de revertir un plástico activo (Causa `00`) hacia un estado de preemisión (Causa `08`). El sistema operativo central solo validará y procesará de forma estricta el paso directo desde **Causa 00 hacia Causa 14**. |

---
*Fin de la Etapa 1 del archivo de configuración de reglas de desarrollo.*
