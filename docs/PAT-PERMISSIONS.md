# PAT Token Configuration

## Required GitHub Permissions

Your PAT token must have the following permissions:

### Repository permissions:
- **Contents**: Read and Write
- **Metadata**: Read
- **Pull requests**: Write (if using PRs)

### Account permissions:
- **Git SSH keys**: Read (if using SSH)

## GitHub Secrets Configuration

1. Go to your repository → Settings → Secrets and variables → Actions
2. Create or update the secret `GIT_TOKEN` with your PAT token
3. Verify that the token is not expired

## Token Verification

You can test your token by running:

```bash
curl -H "Authorization: token YOUR_TOKEN" https://api.github.com/user
```

## Troubleshooting

If you continue having issues:

1. Regenerate the PAT token
2. Verify that you have admin permissions on the repository
3. Make sure branch protection rules allow direct push
