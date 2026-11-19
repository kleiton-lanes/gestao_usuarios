/**
 * TELA (Vue 3)
 *
 * Guarda o estado da página (lista de usuários, formulário, mensagens)
 * e chama o UserApi quando o usuário clica em algo.
 */
const { createApp } = Vue;

createApp({
  // data: as variáveis da tela. Quando elas mudam, o Vue atualiza o HTML sozinho.
  data() {
    return {
      users: [],          
      form: {             
        id: null,
        name: '',
        email: '',
        login: '',
        password: '',
        active: true,
      },
      loading: false,    
      message: null,      
      messageTimer: null, 
    };
  },

  // computed: valor calculado a partir do data
  computed: {
    isEditing() {
      return this.form.id !== null;
    },
  },

  // mounted: roda uma vez, quando a página termina de carregar
  mounted() {
    this.loadUsers();
  },

  methods: {
    // Mostra uma mensagem e a apaga depois de 5 segundos
    showMessage(type, text) {
      this.message = { type: type, text: text };
      clearTimeout(this.messageTimer);
      this.messageTimer = setTimeout(() => {
        this.message = null;
      }, 5000);
    },

    // Volta o formulário para o estado de "novo usuário"
    clearForm() {
      this.form = {
        id: null,
        name: '',
        email: '',
        login: '',
        password: '',
        active: true,
      };
    },

    // Busca a lista na API
    async loadUsers() {
      this.loading = true;
      try {
        this.users = await UserApi.list();
      } catch (error) {
        this.showMessage('error', error.message);
      } finally {
        this.loading = false; // roda sempre, deu certo ou não
      }
    },

    // Botão do formulário: cadastra ou edita, dependendo de haver id
    async save() {
      const user = {
        name: this.form.name,
        email: this.form.email,
        login: this.form.login,
        active: this.form.active,
      };

      // Na edição, senha em branco significa "manter a senha atual"
      if (this.form.password !== '') {
        user.password = this.form.password;
      }

      this.loading = true;
      try {
        if (this.isEditing) {
          await UserApi.update(this.form.id, user);
          this.showMessage('success', 'Usuário atualizado com sucesso!');
        } else {
          await UserApi.create(user);
          this.showMessage('success', 'Usuário cadastrado com sucesso!');
        }
        this.clearForm();
        await this.loadUsers();
      } catch (error) {
        this.showMessage('error', error.message);
      } finally {
        this.loading = false;
      }
    },

    // Botão "Editar": copia os dados da linha para o formulário
    startEdit(user) {
      this.form = {
        id: user.id,
        name: user.name,
        email: user.email,
        login: user.login,
        password: '', // a senha nunca vem da API, então o campo começa vazio
        active: user.active,
      };
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    // Botão "Excluir": pede confirmação antes de apagar
    async remove(user) {
      const confirmed = confirm('Tem certeza que deseja excluir "' + user.name + '"?');
      if (!confirmed) {
        return; // o usuário desistiu
      }

      this.loading = true;
      try {
        await UserApi.remove(user.id);
        this.showMessage('success', 'Usuário excluído com sucesso!');
        if (this.form.id === user.id) {
          this.clearForm(); // se estava editando o usuário excluído, limpa o formulário
        }
        await this.loadUsers();
      } catch (error) {
        this.showMessage('error', error.message);
      } finally {
        this.loading = false;
      }
    },
  },
}).mount('#app');
