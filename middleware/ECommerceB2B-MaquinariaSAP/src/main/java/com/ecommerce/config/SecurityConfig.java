package com.ecommerce.config;

import org.springframework.context.annotation.Configuration;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.annotation.web.configuration.EnableWebSecurity;
import org.springframework.security.config.annotation.web.configuration.WebSecurityConfigurerAdapter;

/**
 * Configuración de seguridad para habilitar autenticación básica en los endpoints API.
 */
@Configuration
@EnableWebSecurity
public class SecurityConfig extends WebSecurityConfigurerAdapter {
    @Override
    protected void configure(HttpSecurity http) throws Exception {
        http.csrf().disable()
            .authorizeRequests()
            // Endpoints públicos:
            .antMatchers("/", "/error", "/odata/**").permitAll()
            // Endpoints que requieren autenticación:
            .antMatchers("/api/**").authenticated()
            .and()
            .httpBasic();
    }
}
