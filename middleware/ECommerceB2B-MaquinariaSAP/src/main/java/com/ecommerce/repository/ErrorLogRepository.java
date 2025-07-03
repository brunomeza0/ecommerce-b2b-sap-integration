package com.ecommerce.repository;

import com.ecommerce.model.ErrorLog;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface ErrorLogRepository extends JpaRepository<ErrorLog, Integer> {
    // Métodos personalizados pueden definirse aquí si es necesario.
}
