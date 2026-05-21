package com.example.gestor_de_pagamentos.service;

import com.example.gestor_de_pagamentos.model.Fatura;
import com.example.gestor_de_pagamentos.repository.FaturaRepository;
import org.springframework.stereotype.Service;
import java.util.List;

@Service
public class FaturaService {

    private final FaturaRepository repository;

    public FaturaService(FaturaRepository repository) {
        this.repository = repository;
    }

    public List<Fatura> listarTodas() {
        return repository.findAll();
    }

    public List<Fatura> listarPorCliente(Long clienteId) {
        return repository.findByClienteId(clienteId);
    }

    public Fatura buscarPorId(Long id) {
        return repository.findById(id)
                .orElseThrow(() -> new RuntimeException("Fatura nao encontrada"));
    }

    public Fatura salvar(Fatura fatura) {
        return repository.save(fatura);
    }

    public void deletar(Long id) {
        repository.deleteById(id);
    }
}