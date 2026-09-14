/**
 * Helper utility to parse and format SIPUAS forwarded tickets in PESU PELUH.
 */
export function parseSipuasTicket(ticket) {
    if (!ticket) return { isSipuas: false };

    const desc = ticket.problem_description || '';
    const isSipuas = desc.includes('[DISPOSISI ADUAN PUBLIK SIPUAS]') || 
                     ticket.reporter?.username === 'sipuas_masyarakat' ||
                     ticket.reporter?.username?.startsWith('sipuas_');

    if (!isSipuas) {
        return { isSipuas: false };
    }

    // 1. Extract SIPUAS Ticket Number
    const ticketMatch = desc.match(/No\.\s*Tiket\s*SIPUAS:\s*(.*?)(?:\s+Lokasi|\s+Ruangan|\s+Pelapor|[\r\n]|$)/i);
    const sipuasTicketNumber = ticketMatch ? ticketMatch[1].trim() : null;

    // 2. Extract Reporter Raw String
    const reporterMatch = desc.match(/Pelapor:\s*(.*?)(?:\s+Diteruskan|\s+---|[\r\n]|$)/i);
    const rawReporter = reporterMatch ? reporterMatch[1].trim() : (ticket.reporter?.name || 'Masyarakat');

    let reporterName = rawReporter;
    let reporterPhone = ticket.reporter?.phone_number && ticket.reporter.phone_number !== '-' 
        ? ticket.reporter.phone_number 
        : null;

    // Check for phone number in "Pelapor: Name (HP: 08xxx)"
    const hpMatch = rawReporter.match(/\(HP:\s*([^)]+)\)/i);
    if (hpMatch) {
        reporterPhone = hpMatch[1].trim();
        reporterName = rawReporter.replace(/\(HP:\s*[^)]+\)/i, '').trim();
    }

    // Clean up reporter name
    reporterName = reporterName.replace(/\(Publik via SIPUAS\)/i, '').trim();

    // 3. Extract Forwarded By (KASI / Verifikator)
    const forwardedMatch = desc.match(/Diteruskan\s*oleh:\s*(.*?)(?:\s+---|\s+Catatan|[\r\n]|$)/i);
    const forwardedBy = forwardedMatch ? forwardedMatch[1].trim() : 'KASI Verifikator SIPUAS';

    // 4. Extract Problem Description and Supervisor Notes
    let cleanDescription = desc;
    let supervisorNotes = null;

    const uraianSplit = desc.split(/---\s*URAIAN KELUHAN FASILITAS\s*---/i);
    if (uraianSplit.length > 1) {
        const afterUraian = uraianSplit[1];
        const catatanSplit = afterUraian.split(/---\s*CATATAN VERIFIKATOR\s*(?:\([^)]*\))?\s*---/i);
        cleanDescription = catatanSplit[0].trim();
        if (catatanSplit.length > 1) {
            supervisorNotes = catatanSplit[1].trim();
        }
    } else {
        const catatanSplit = desc.split(/---\s*CATATAN VERIFIKATOR\s*(?:\([^)]*\))?\s*---/i);
        if (catatanSplit.length > 1) {
            cleanDescription = catatanSplit[0].trim();
            supervisorNotes = catatanSplit[1].trim();
        }
    }

    return {
        isSipuas: true,
        sipuasTicketNumber,
        reporterName: reporterName || 'Masyarakat / Pasien',
        reporterPhone: reporterPhone || '-',
        forwardedBy,
        cleanDescription: cleanDescription || desc,
        supervisorNotes: supervisorNotes || null,
    };
}

export function isSipuasTicket(ticket) {
    if (!ticket) return false;
    return parseSipuasTicket(ticket).isSipuas;
}

export function getDisplayDescription(ticket) {
    if (!ticket) return '';
    const parsed = parseSipuasTicket(ticket);
    return parsed.isSipuas ? parsed.cleanDescription : (ticket.problem_description || '');
}

export function getDisplayReporterName(ticket) {
    if (!ticket) return '-';
    const parsed = parseSipuasTicket(ticket);
    return parsed.isSipuas ? parsed.reporterName : (ticket.reporter?.name || '-');
}

export function getDisplayReporterPhone(ticket) {
    if (!ticket) return '-';
    const parsed = parseSipuasTicket(ticket);
    return parsed.isSipuas ? parsed.reporterPhone : (ticket.reporter?.phone_number || '-');
}
