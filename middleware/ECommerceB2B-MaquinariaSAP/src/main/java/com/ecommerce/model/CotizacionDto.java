// CotizacionDto.java
package com.ecommerce.model;

import java.util.List;

/**
 * DTO que representa una cotización recibida del eCommerce.
 */
public class CotizacionDto {
    private String correlationId;
    private String quoteId;         // ID interno de la cotización en el eCommerce
    private String customerCode;    // Código de cliente SAP (KUNNR)
    private String initialDate;     // Fecha inicio validez formateada como YYYYMMDD
    private String finalDate;       // Fecha fin validez formateada como YYYYMMDD
    private List<CotizacionItemDto> items;

    public CotizacionDto() {}

    public CotizacionDto(String correlationId, String quoteId, String customerCode,
                         String initialDate, String finalDate, List<CotizacionItemDto> items) {
        this.correlationId = correlationId;
        this.quoteId       = quoteId;
        this.customerCode  = customerCode;
        this.initialDate   = initialDate;
        this.finalDate     = finalDate;
        this.items         = items;
    }

    public String getCorrelationId() {
        return correlationId;
    }
    public void setCorrelationId(String correlationId) {
        this.correlationId = correlationId;
    }

    public String getQuoteId() {
        return quoteId;
    }
    public void setQuoteId(String quoteId) {
        this.quoteId = quoteId;
    }

    public String getCustomerCode() {
        return customerCode;
    }
    public void setCustomerCode(String customerCode) {
        this.customerCode = customerCode;
    }

    public String getInitialDate() {
        return initialDate;
    }
    public void setInitialDate(String initialDate) {
        this.initialDate = initialDate;
    }

    public String getFinalDate() {
        return finalDate;
    }
    public void setFinalDate(String finalDate) {
        this.finalDate = finalDate;
    }

    public List<CotizacionItemDto> getItems() {
        return items;
    }
    public void setItems(List<CotizacionItemDto> items) {
        this.items = items;
    }

    @Override
    public String toString() {
        return "CotizacionDto{" +
               "correlationId='" + correlationId + '\'' +
               ", quoteId='" + quoteId + '\'' +
               ", customerCode='" + customerCode + '\'' +
               ", initialDate='" + initialDate + '\'' +
               ", finalDate='" + finalDate + '\'' +
               ", items=" + (items != null ? items.size() : 0) +
               '}';
    }
}
