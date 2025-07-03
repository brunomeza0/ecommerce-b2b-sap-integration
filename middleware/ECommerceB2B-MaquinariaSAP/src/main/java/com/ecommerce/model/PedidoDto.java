package com.ecommerce.model;

import java.util.List;

/**
 * DTO que representa un pedido recibido del eCommerce.
 */
public class PedidoDto {
    private String correlationId;
    private Integer pedidoId;
    private Integer cotizacionId;
    private String cliente;
    private Double igv;
    private Double tipoCambio;
    private String fechaDespacho;
    private String direccionEntrega;
    private List<PedidoItemDto> items;

    // Getters y setters...
    public String getCorrelationId() { return correlationId; }
    public void setCorrelationId(String correlationId) { this.correlationId = correlationId; }
    public Integer getPedidoId() { return pedidoId; }
    public void setPedidoId(Integer pedidoId) { this.pedidoId = pedidoId; }
    public Integer getCotizacionId() { return cotizacionId; }
    public void setCotizacionId(Integer cotizacionId) { this.cotizacionId = cotizacionId; }
    public String getCliente() { return cliente; }
    public void setCliente(String cliente) { this.cliente = cliente; }
    public Double getIgv() { return igv; }
    public void setIgv(Double igv) { this.igv = igv; }
    public Double getTipoCambio() { return tipoCambio; }
    public void setTipoCambio(Double tipoCambio) { this.tipoCambio = tipoCambio; }
    public String getFechaDespacho() { return fechaDespacho; }
    public void setFechaDespacho(String fechaDespacho) { this.fechaDespacho = fechaDespacho; }
    public String getDireccionEntrega() { return direccionEntrega; }
    public void setDireccionEntrega(String direccionEntrega) { this.direccionEntrega = direccionEntrega; }
    public List<PedidoItemDto> getItems() { return items; }
    public void setItems(List<PedidoItemDto> items) { this.items = items; }
}