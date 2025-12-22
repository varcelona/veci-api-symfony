import { Controller } from '@hotwired/stimulus'
import { Modal } from 'bootstrap'

export default class extends Controller {
  static targets = ['body']
  static values = {
    storeId: Number,
    buttonId: { type: String, default: 'btnStoreSchedule' }
  }

  connect() {
    // Instancia una sola vez
    this.modalInstance = new Modal(this.element)

    // Buscar botón por ID (esté donde esté)
    this.buttonEl = document.getElementById(this.buttonIdValue)

    if (!this.buttonEl) {
      console.warn(`[store-schedule] No se encontró botón #${this.buttonIdValue}`)
      return
    }

    // Bind
    this._onClick = (e) => this.open(e)
    this.buttonEl.addEventListener('click', this._onClick)
  }

  disconnect() {
    if (this.buttonEl && this._onClick) {
      this.buttonEl.removeEventListener('click', this._onClick)
    }
  }

  open(event) {
    event.preventDefault()

    this.modalInstance.show()
    this.bodyTarget.innerHTML = `<div class="text-center text-muted py-5">Cargando horarios…</div>`

    fetch(`/admin/store/${this.storeIdValue}/schedule/modal`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.text())
      .then(html => { this.bodyTarget.innerHTML = html })
  }

  update(event) {
    const row = event.target.closest('tr')
    const id = row.dataset.id

    const inputs = row.querySelectorAll('input')
    const payload = {
      from: inputs[0].value || null,
      to: inputs[1].value || null,
      open: inputs[2].checked
    }

    fetch(`/admin/store/${this.storeIdValue}/schedule/${id}/ajax-update`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(payload)
    })
  }

  delete(event) {
    const row = event.target.closest('tr')
    const id = row.dataset.id

    if (!confirm('¿Eliminar horario?')) return

    fetch(`/admin/store/${this.storeIdValue}/schedule/${id}/ajax-delete`, {
      method: 'DELETE',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(async (r) => {
        const data = await r.json()

        if (!r.ok || !data.success) {
          throw new Error(data.message || 'Error')
        }

        // éxito
        row.remove()
        this.showMessage(data.message, 'success')
      })
      .catch((error) => {
        this.showMessage(error.message, 'danger')
      })
  }

  add() {
    // Día por defecto: lunes (podés mejorar esto luego)
    const payload = {
      weekDay: 'monday',
      from: '09:00',
      to: '18:00'
    }

    fetch(`/admin/store/${this.storeIdValue}/schedule/ajax-create`, {
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
          throw new Error(data.message)
        }

        // Insertar nueva fila
        const tbody = this.bodyTarget.querySelector('tbody')
        tbody.insertAdjacentHTML('beforeend', data.html)

        this.showMessage(data.message, 'success')
      })
      .catch(err => {
        this.showMessage(err.message, 'danger')
      })
  }


}
