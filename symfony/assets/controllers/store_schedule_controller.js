import { Controller } from '@hotwired/stimulus'
import { Modal } from 'bootstrap'

export default class extends Controller {
  static targets = ['body', 'flash']
  static values = {
    storeId: Number,
    buttonId: { type: String, default: 'btnStoreSchedule' }
  }

  connect() {
    // Modal Bootstrap (una sola instancia)
    this.modalInstance = new Modal(this.element)

    // Buscar botón dentro del form por ID
    this.buttonEl = document.getElementById(this.buttonIdValue)

    if (!this.buttonEl) {
      console.warn(`[store-schedule] Botón #${this.buttonIdValue} no encontrado`)
      return
    }

    this._onClick = (e) => this.open(e)
    this.buttonEl.addEventListener('click', this._onClick)
  }

  disconnect() {
    if (this.buttonEl && this._onClick) {
      this.buttonEl.removeEventListener('click', this._onClick)
    }
  }

  /* ===========================
   * MODAL
   * =========================== */

  open(event) {
    event.preventDefault()

    this.modalInstance.show()
    this.bodyTarget.innerHTML =
      `<div class="text-center text-muted py-5">Cargando horarios…</div>`

    fetch(`/admin/store/${this.storeIdValue}/schedule/modal`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.text())
      .then(html => {
        this.bodyTarget.innerHTML = html
      })
      .catch(() => {
        this.showMessage('Error al cargar horarios', 'danger')
      })
  }

  /* ===========================
   * NUEVO HORARIO (NO persistido)
   * =========================== */

  addRow() {
    const tbody = this.bodyTarget.querySelector('tbody')
    if (!tbody) return

    // Evitar múltiples filas nuevas simultáneas
    if (tbody.querySelector('tr[data-new="1"]')) {
      this.showMessage('Guardá o cancelá el horario pendiente primero.', 'warning')
      return
    }

    fetch(`/admin/store/${this.storeIdValue}/schedule/new-row-template`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.text())
      .then(html => {
        tbody.insertAdjacentHTML('beforeend', html)
      })
  }

  cancelNew(event) {
    event.target.closest('tr').remove()
  }

  saveNew(event) {
    const row = event.target.closest('tr')
    const inputs = row.querySelectorAll('select, input')

    const payload = {
      weekDay: inputs[0].value,
      from: inputs[1].value,
      to: inputs[2].value,
      open: inputs[3].checked
    }

    fetch(`/admin/store/${this.storeIdValue}/schedule/save`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(payload)
    })
      .then(async r => {
        const data = await r.json()
        if (!r.ok || !data.success) {
          throw new Error(data.message || 'Error')
        }

        // Reemplazar fila temporal por fila real
        row.outerHTML = data.html
        this.showMessage(data.message, 'success')
      })
      .catch(err => {
        this.showMessage(err.message, 'danger')
      })
  }

  /* ===========================
   * UPDATE INLINE
   * =========================== */

  update(event) {
    const row = event.target.closest('tr')
    if (!row || row.dataset.new) return

    const id = row.dataset.id
    const inputs = row.querySelectorAll('input')

    const payload = {
      from: inputs[0].value || null,
      to: inputs[1].value || null,
      open: inputs[2].checked
    }

    fetch(`/admin/store/${this.storeIdValue}/schedule/${id}/edit`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(payload)
    })
  }

  /* ===========================
   * DELETE
   * =========================== */

  delete(event) {
    const row = event.target.closest('tr')
    const id = row.dataset.id

    if (!confirm('¿Eliminar horario?')) return

    fetch(`/admin/store/${this.storeIdValue}/schedule/${id}/delete`, {
      method: 'DELETE',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(async r => {
        const data = await r.json()
        if (!r.ok || !data.success) {
          throw new Error(data.message)
        }

        row.remove()
        this.showMessage(data.message, 'success')
      })
      .catch(err => {
        this.showMessage(err.message, 'danger')
      })
  }

  /* ===========================
   * FEEDBACK
   * =========================== */

  showMessage(message, type = 'success') {
    if (!this.hasFlashTarget) return

    this.flashTarget.innerHTML = `
      <div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    `
  }
}
