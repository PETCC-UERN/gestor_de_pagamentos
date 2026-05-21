export default function FaturaList({ faturas, onDeletar }) {
    return (
        <div className="mt-6">
            {faturas.length === 0 && (
                <p className="text-gray-500">Nenhuma fatura cadastrada.</p>
            )}
            {faturas.map((fatura) => (
                <div
                    key={fatura.id}
                    className="flex justify-between items-center border rounded p-4 mb-2"
                >
                    <div>
                        <p className="font-semibold">R$ {fatura.valor}</p>
                        <p className="text-sm text-gray-500">
                            Vencimento: {fatura.vencimento} — Status: {fatura.status}
                        </p>
                    </div>
                    <button
                        onClick={() => onDeletar(fatura.id)}
                        className="text-red-500 hover:text-red-700 text-sm"
                    >
                        Remover
                    </button>
                </div>
            ))}
        </div>
    )
}