/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package com.ecommerce.odata;

import org.apache.olingo.odata2.core.servlet.ODataServlet;
import org.springframework.boot.web.servlet.ServletRegistrationBean;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
/**
 *
 * @author pc
 */
@Configuration
public class ODataConfig {

    @Bean
    public ServletRegistrationBean<ODataServlet> odataServlet() {
        ODataServlet servlet = new ODataServlet();
        ServletRegistrationBean<ODataServlet> bean =
                new ServletRegistrationBean<>(servlet, "/odata/*");
        
        // Cambiar la clase de la fábrica de servicios
        bean.addInitParameter("org.apache.olingo.odata2.service.factory",
                              "com.ecommerce.odata.MyODataServiceFactory");
        return bean;
    }
}