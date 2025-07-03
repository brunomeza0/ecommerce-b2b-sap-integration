// CotizacionService.java
package com.ecommerce.service;

import com.ecommerce.model.CotizacionDto;

/**
 * Servicio para procesamiento de cotizaciones hacia SAP.
 */
public interface CotizacionService {
    /**
     * Transforma la cotización en formato CSV y la envía a SAP (vía archivo en directorio compartido).
     * @param cotizacion objeto con los datos de la cotización a procesar.
     * @throws Exception si ocurre un error al escribir el archivo CSV.
     */
    void enviarCotizacionASap(CotizacionDto cotizacion) throws Exception;
}
