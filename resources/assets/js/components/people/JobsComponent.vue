<template>
  <div>
    <form>
      <div class="form-group" v-for="(job,index) in jobs">
        <span class="float-right" style="cursor:pointer"
            @click="remove(index)"> 
            X
        </span>    
        <label>Job Title</label>
        <input type="text" class="form-control" placeholder="Job title" v-model="job.jobTitle">
        <label>Company</label>
        <input type="text" class="form-control" placeholder="Company" v-model="job.company">
      </div>  
    </form>
    <form>
      <div class="form-group" v-for="(job,index) in pastJobs[0]">
        <label>Past job Title</label>
        <input type="text" class="form-control" placeholder="Past job title" v-model="job.past_job">
        <label>Past company</label>
        <input type="text" class="form-control" placeholder="Past company" v-model="job.past_company">
      </div>  
    </form>  
    <button  @click="addJob" class="btn btn-primary">Add Past Job</button>
    <button  @click="savePassJob" class="btn btn-primary">Save Past Job</button>
    <button  @click="updatePassJob" class="btn btn-primary">Update Past Job</button>
  </div>
</template>
<script>
export default {
  props : ['contactId','pastJobs'],
  data: function() {
    return {
      jobs: [{ contactId: this.contactId }],
      updateJobs: [{  contactId: this.contactId }]
    }
  },

  mounted: function(){
    
  },

  methods:{
    addJob: function(){
      // this.pastJobs = this.jobs;
      this.jobs.push({
        contactId: this.contactId,
        jobTitle: '',
        company: '',
      })
    },

    remove: function(index){
      this.jobs.splice(index,1);
    },

    savePassJob: function(){
      axios.post(`/people/${this.contactId}/work/add`,this.jobs)
        .then(({ jobs }) =>{
            //console.log(this.jobs);
        },({ response }) =>{
            this.handleErrorResponse(response, onError);
        });
    },

    updatePassJob: function(){
      this.updateJobs = this.pastJobs;
      console.log(this.updateJobs);
      axios.put(`/people/${this.contactId}/work/updatePastJob`,this.updateJobs)
        .then(({ jobs }) =>{
            //console.log(this.jobs);
        },({ response }) =>{
            this.handleErrorResponse(response, onError);
        });
    },

  }

}
</script>

