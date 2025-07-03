/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package com.ecommerce.controller;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;

/**
 *
 * @author pc
 */
@RestController
public class HomeController {

    @GetMapping("/")
    public String home() {
        return "Bienvenido a la aplicación ECommerceB2B-MaquinariaSAP";
    }
}