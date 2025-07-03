/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package com.ecommerce.odata;

import org.apache.olingo.odata2.api.edm.EdmException;
import org.apache.olingo.odata2.api.edm.provider.EdmProvider;
import org.apache.olingo.odata2.api.edm.provider.Schema;
import org.apache.olingo.odata2.api.edm.provider.EntityContainerInfo;
import java.util.ArrayList;
import java.util.List;
/**
 *
 * @author pc
 */
public class MyEdmProvider extends EdmProvider {

    @Override
    public List<Schema> getSchemas() throws EdmException {
        // Crea un esquema básico con un namespace
        Schema schema = new Schema();
        schema.setNamespace("ODataDemo");
        // Aquí se definirían las entidades y otros elementos del modelo
        List<Schema> schemas = new ArrayList<>();
        schemas.add(schema);
        return schemas;
    }

    @Override
    public EntityContainerInfo getEntityContainerInfo(String name) throws EdmException {
        if (name == null || name.isEmpty()) {
            // Define un contenedor por defecto
            return new EntityContainerInfo().setName("DefaultContainer").setDefaultEntityContainer(true);
        }
        return null;
    }
}