/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package com.ecommerce.service;
import com.ecommerce.model.Producto;
import java.util.List;
/**
 *
 * @author pc
 */
public interface ProductoService {
    List<Producto> listar();
    Producto guardar(Producto p);
    Producto obtenerPorId(Long id);
    void eliminar(Long id);
}