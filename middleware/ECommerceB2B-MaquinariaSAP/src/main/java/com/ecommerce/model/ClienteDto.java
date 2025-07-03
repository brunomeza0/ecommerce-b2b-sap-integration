package com.ecommerce.model;
import com.fasterxml.jackson.annotation.JsonProperty;

public class ClienteDto {

    @JsonProperty("Cliente")
    private String cliente;
    @JsonProperty("RazónSocial")
    private String razonSocial;
    @JsonProperty("Dirección")
    private String direccion;
    @JsonProperty("Ciudad")
    private String ciudad;
    @JsonProperty("Región")
    private String region;
    @JsonProperty("CP")
    private String cp;
    @JsonProperty("País")
    private String pais;
    @JsonProperty("Centro")
    private String centro;
    @JsonProperty("OrgVenta")
    private String orgVenta;
    @JsonProperty("Canal")
    private String canal;
    @JsonProperty("División")
    private String division;
    @JsonProperty("Moneda")
    private String moneda;
    @JsonProperty("GrpPrecio")
    private String grpPrecio;
    @JsonProperty("CondPago")
    private String condPago;

    public ClienteDto() {
    }

    public ClienteDto(String cliente, String razonSocial, String direccion, String ciudad,
                      String region, String cp, String pais, String centro,
                      String orgVenta, String canal, String division,
                      String moneda, String grpPrecio, String condPago) {
        this.cliente = cliente;
        this.razonSocial = razonSocial;
        this.direccion = direccion;
        this.ciudad = ciudad;
        this.region = region;
        this.cp = cp;
        this.pais = pais;
        this.centro = centro;
        this.orgVenta = orgVenta;
        this.canal = canal;
        this.division = division;
        this.moneda = moneda;
        this.grpPrecio = grpPrecio;
        this.condPago = condPago;
    }

    public String getCliente() { return cliente; }
    public void setCliente(String cliente) { this.cliente = cliente; }

    public String getRazonSocial() { return razonSocial; }
    public void setRazonSocial(String razonSocial) { this.razonSocial = razonSocial; }

    public String getDireccion() { return direccion; }
    public void setDireccion(String direccion) { this.direccion = direccion; }

    public String getCiudad() { return ciudad; }
    public void setCiudad(String ciudad) { this.ciudad = ciudad; }

    public String getRegion() { return region; }
    public void setRegion(String region) { this.region = region; }

    public String getCp() { return cp; }
    public void setCp(String cp) { this.cp = cp; }

    public String getPais() { return pais; }
    public void setPais(String pais) { this.pais = pais; }

    public String getCentro() { return centro; }
    public void setCentro(String centro) { this.centro = centro; }

    public String getOrgVenta() { return orgVenta; }
    public void setOrgVenta(String orgVenta) { this.orgVenta = orgVenta; }

    public String getCanal() { return canal; }
    public void setCanal(String canal) { this.canal = canal; }

    public String getDivision() { return division; }
    public void setDivision(String division) { this.division = division; }

    public String getMoneda() { return moneda; }
    public void setMoneda(String moneda) { this.moneda = moneda; }

    public String getGrpPrecio() { return grpPrecio; }
    public void setGrpPrecio(String grpPrecio) { this.grpPrecio = grpPrecio; }

    public String getCondPago() { return condPago; }
    public void setCondPago(String condPago) { this.condPago = condPago; }
}