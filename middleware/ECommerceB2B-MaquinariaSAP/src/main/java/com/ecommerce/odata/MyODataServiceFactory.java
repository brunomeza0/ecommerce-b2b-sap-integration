/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package com.ecommerce.odata;
import org.apache.olingo.odata2.api.ODataService;
import org.apache.olingo.odata2.api.ODataServiceFactory;
import org.apache.olingo.odata2.api.processor.ODataContext;
/**
 *
 * @author pc
 */
public class MyODataServiceFactory extends ODataServiceFactory {
    @Override
    public ODataService createService(ODataContext ctx) {
        // Pasa como primer parámetro el EdmProvider y como segundo el processor
        return createODataSingleProcessorService(new MyEdmProvider(), new ODataSingleProcessorImpl());
    }
}