import { useEffect, useState } from 'react'
import api from '../api/axios'
import FaturaForm from '../components/FaturaForm'
import FaturaList from '../components/FaturaList'

export default function Faturas() {
    const [faturas, setFaturas] = useState([])

    async function carregar() {
        const response = await api.get('/faturas')
        setFaturas(response.data)
    }

    async function salvar(dados) {
        await api.post('/faturas', dados)
        carregar()
    }

    async function deletar(id) {
        await api.delete(`/faturas/${id}`)
        carregar()
    }

    useEffect(() => {
        carregar()
    }, [])

    return (
        <div className="max-w-2xl mx-auto p-6">
            <h1 className="text-2xl font-bold mb-4">Faturas</h1>
            <FaturaForm onSalvar={salvar} />
            <FaturaList faturas={faturas} onDeletar={deletar} />
        </div>
    )
}