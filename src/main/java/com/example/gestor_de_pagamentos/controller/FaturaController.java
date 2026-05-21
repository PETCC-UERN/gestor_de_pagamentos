package com.example.gestor_de_pagamentos.controller;

import com.example.gestor_de_pagamentos.model.Fatura;
import com.example.gestor_de_pagamentos.service.FaturaService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.support.ServletUriComponentsBuilder;
import java.net.URI;
import java.util.List;

@RestController
@RequestMapping("/api/v1/faturas")
public class FaturaController {

    private final FaturaService service;

    public FaturaController(FaturaService service) {
        this.service = service;
    }

    @GetMapping
    public ResponseEntity<List<Fatura>> listar() {
        return ResponseEntity.ok(service.listarTodas());
    }

    @GetMapping("/cliente/{clienteId}")
    public ResponseEntity<List<Fatura>> listarPorCliente(@PathVariable Long clienteId) {
        return ResponseEntity.ok(service.listarPorCliente(clienteId));
    }

    @GetMapping("/{id}")
    public ResponseEntity<Fatura> buscar(@PathVariable Long id) {
        return ResponseEntity.ok(service.buscarPorId(id));
    }

    @PostMapping
    public ResponseEntity<Fatura> criar(@RequestBody Fatura fatura) {
        Fatura salva = service.salvar(fatura);
        URI location = ServletUriComponentsBuilder
                .fromCurrentRequest()
                .path("/{id}")
                .buildAndExpand(salva.getId())
                .toUri();
        return ResponseEntity.created(location).body(salva);
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> deletar(@PathVariable Long id) {
        service.deletar(id);
        return ResponseEntity.noContent().build();
    }
}