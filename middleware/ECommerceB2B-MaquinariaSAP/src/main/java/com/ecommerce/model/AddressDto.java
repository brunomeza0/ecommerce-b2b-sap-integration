// src/main/java/com/ecommerce/model/AddressDto.java
package com.ecommerce.model;
import com.fasterxml.jackson.annotation.JsonProperty;


/**
 * DTO para override de dirección en despacho.
 */
public class AddressDto {

    @JsonProperty("Name1")
    private String Name1;

    @JsonProperty("Street")
    private String Street;

    @JsonProperty("PostalCode")
    private String PostalCode;

    @JsonProperty("City")
    private String City;

    @JsonProperty("Country")
    private String Country;

    // Constructor vacío
    public AddressDto() {
    }

    // Constructor con todos los campos
    public AddressDto(String Name1, String Street, String PostalCode, String City, String Country) {
        this.Name1 = Name1;
        this.Street = Street;
        this.PostalCode = PostalCode;
        this.City = City;
        this.Country = Country;
    }

    // Getter y Setter para Name1
    public String getName1() {
        return Name1;
    }

    public void setName1(String Name1) {
        this.Name1 = Name1;
    }

    // Getter y Setter para Street
    public String getStreet() {
        return Street;
    }

    public void setStreet(String Street) {
        this.Street = Street;
    }

    // Getter y Setter para PostalCode
    public String getPostalCode() {
        return PostalCode;
    }

    public void setPostalCode(String PostalCode) {
        this.PostalCode = PostalCode;
    }

    // Getter y Setter para City
    public String getCity() {
        return City;
    }

    public void setCity(String City) {
        this.City = City;
    }

    // Getter y Setter para Country
    public String getCountry() {
        return Country;
    }

    public void setCountry(String Country) {
        this.Country = Country;
    }

    @Override
    public String toString() {
        return "AddressDto{" +
               "Name1='" + Name1 + '\'' +
               ", Street='" + Street + '\'' +
               ", PostalCode='" + PostalCode + '\'' +
               ", City='" + City + '\'' +
               ", Country='" + Country + '\'' +
               '}';
    }
}
