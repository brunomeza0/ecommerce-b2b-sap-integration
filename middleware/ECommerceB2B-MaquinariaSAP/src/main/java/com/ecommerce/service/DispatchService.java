package com.ecommerce.service;

import com.ecommerce.model.DispatchDto;

public interface DispatchService {
    /**
     * Toma el DTO desde e-commerce y genera el CSV de despacho en carpeta SAP_IN.
     * @param despacho información de despacho
     * @throws Exception si falla la generación/escritura
     */
    void enviarDespachoASap(DispatchDto despacho) throws Exception;
}
