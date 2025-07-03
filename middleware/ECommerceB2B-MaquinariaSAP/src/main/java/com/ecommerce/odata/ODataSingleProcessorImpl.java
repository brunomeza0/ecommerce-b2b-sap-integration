/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package com.ecommerce.odata;
import org.apache.olingo.odata2.api.commons.HttpStatusCodes;
import org.apache.olingo.odata2.api.processor.ODataSingleProcessor;
import org.apache.olingo.odata2.api.processor.ODataResponse;
import org.apache.olingo.odata2.api.uri.info.GetEntitySetUriInfo;
import org.apache.olingo.odata2.api.uri.info.GetEntityUriInfo;

/**
 *
 * @author pc
 */


public class ODataSingleProcessorImpl extends ODataSingleProcessor {

    @Override
    public ODataResponse readEntitySet(GetEntitySetUriInfo uriInfo, String contentType) {
        // Usar HttpStatusCodes en lugar de enteros (200, 201)
        return ODataResponse.newBuilder()
                .status(HttpStatusCodes.OK)
                .entity("Listado de entidades OData")
                .build();
    }

    @Override
    public ODataResponse readEntity(GetEntityUriInfo uriInfo, String contentType) {
        return ODataResponse.newBuilder()
                .status(HttpStatusCodes.OK)
                .entity("Entidad OData")
                .build();
    }
}