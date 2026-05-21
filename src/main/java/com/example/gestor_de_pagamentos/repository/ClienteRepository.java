package com.example.gestor_de_pagamentos.repository;

import com.example.gestor_de_pagamentos.model.Cliente;
import org.springframework.data.jpa.repository.JpaRepository;

public interface ClienteRepository extends JpaRepository<Cliente, Long> {
}