// CotizacionRespuestaDto.java
package com.ecommerce.model;

/**
 * DTO para la respuesta que se envía de regreso al eCommerce via webhook.
 * Contiene el resultado de la creación de la cotización en SAP.
 */
public class CotizacionRespuestaDto {
    private String correlationId;
    private String sapQuoteId;
    private String status;

    public CotizacionRespuestaDto() {}

    public CotizacionRespuestaDto(String correlationId, String sapQuoteId, String status) {
        this.correlationId = correlationId;
        this.sapQuoteId    = sapQuoteId;
        this.status        = status;
    }

    public String getCorrelationId() {
        return correlationId;
    }
    public void setCorrelationId(String correlationId) {
        this.correlationId = correlationId;
    }

    public String getSapQuoteId() {
        return sapQuoteId;
    }
    public void setSapQuoteId(String sapQuoteId) {
        this.sapQuoteId = sapQuoteId;
    }

    public String getStatus() {
        return status;
    }
    public void setStatus(String status) {
        this.status = status;
    }

    @Override
    public String toString() {
        return "CotizacionRespuestaDto{" +
               "correlationId='" + correlationId + '\'' +
               ", sapQuoteId='" + sapQuoteId + '\'' +
               ", status='" + status + '\'' +
               '}';
    }
}
