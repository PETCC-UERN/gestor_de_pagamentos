package com.example.gestor_de_pagamentos.repository;


import com.example.gestor_de_pagamentos.model.Fatura;
import org.springframework.data.jpa.repository.JpaRepository;
import java.util.List;

public interface FaturaRepository extends JpaRepository<Fatura, Long> {
    List<Fatura> findByClienteId(Long clienteId);
}