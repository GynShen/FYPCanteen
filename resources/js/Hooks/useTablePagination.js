import { TABLE_PAGE_SIZE } from '@/Components/TablePagination';
import { useEffect, useState } from 'react';

export default function useTablePagination(items) {
    const [currentPage, setCurrentPage] = useState(1);
    const totalPages = Math.max(1, Math.ceil(items.length / TABLE_PAGE_SIZE));

    useEffect(() => {
        if (currentPage > totalPages) setCurrentPage(totalPages);
    }, [currentPage, totalPages]);

    return {
        currentPage,
        setCurrentPage,
        paginatedItems: items.slice(
            (currentPage - 1) * TABLE_PAGE_SIZE,
            currentPage * TABLE_PAGE_SIZE,
        ),
    };
}
